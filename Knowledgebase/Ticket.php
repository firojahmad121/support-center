<?php

namespace Webkul\UVDesk\SupportCenterBundle\Knowledgebase;

use Webkul\UVDesk\CoreBundle\Entity\Ticket as TicketEntity;
use Webkul\UVDesk\CoreBundle\Entity\Thread;
use Webkul\UVDesk\SupportCenterBundle\Services\UVdeskSupport;
use Webkul\UVDesk\SupportCenterBundle\Form\Ticket as TicketForm;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Ticket extends Controller
{
    protected function isWebsiteActive()
    {
        $error = false;

        $currentWebsite = $this->getWebsiteDetails();
        $isWebsiteActive = $currentWebsite->getIsActive();
        
        $currentWebsite->getIsActive() ?: ($error = true);

        if($error)
            $this->noResultFound();
    }

    protected function getWebsiteDetails()
    {
        $em = $this->getDoctrine()->getManager();
        $websiteRepo = $em->getRepository('UVDeskCoreBundle:Website');

        return $currentWebsite = $websiteRepo->findOneBy(['code' => 'website_branding']);
    }

    /**
     * If customer is playing with url and no result is found then what will happen
     * @return
     */
    protected function noResultFound()
    {
        throw new NotFoundHttpException('Not found !');
    }

    public function ticketadd(Request $request)
    {
        $this->isWebsiteActive();

        $websiteConfiguration = $this->get('uvdesk.service')->getActiveConfiguration($this->getWebsiteDetails()->getId());
        $formErrors = $errors = array();
        if(!$websiteConfiguration || !$websiteConfiguration->getTicketCreateOption() || ($websiteConfiguration->getLoginRequiredToCreate() && !$this->getUser()))
            return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));

        $em = $this->getDoctrine()->getManager();
        $post = $request->request->all();

        if($request->getMethod() == "POST") {
            if('.uvdesk.com' == $this->container->getParameter('site.url') && strpos($this->container->get('router')->getContext()->getHost(), $this->container->getParameter('site.url')) && $this->get('reCaptcha.service')->getReCaptchaResponse($request->request->get('g-recaptcha-response'))){
                $this->addFlash(
                    'warning',
                    $this->get('translator')->trans("Warning ! Please select correct CAPTCHA !")
                );

            } else {
                if($_POST) {
                    $error = false;
                    $message = '';
                    $ticketType = $em->getRepository('UVDeskCoreBundle:TicketType')->find($request->request->get('type'));
                    if($request->files->get('customFields') && !$this->get('file.service')->validateAttachmentsSize($request->files->get('customFields'))) {
                        $error = true;
                        $this->addFlash(
                                'warning',
                                $this->get('translator')->trans("Warning ! Files size can not exceed %size% MB", [
                                    '%size%' => $this->container->getParameter('max_upload_size')
                                ])
                            );
                    }

                    if(!$this->get('file.service')->validateAttachmentsSize($request->files->get('attachments'))) {
                        $error = true;
                        $this->addFlash(
                                'warning',
                                $this->get('translator')->trans("Warning ! Files size can not exceed %size% MB", [
                                    '%size%' => $this->container->getParameter('max_upload_size')
                                ])
                            );
                    }

                    $ticket = new TicketEntity();
                    $loggedUser = $this->get('security.token_storage')->getToken()->getUser();
                    if(!empty($loggedUser) && $loggedUser != 'anon.') {
                        $form = $this->createForm(TicketForm::class, $ticket, [
                            'container' => $this->container,
                            'entity_manager' => $em,
                        ]);
                        $email = $loggedUser->getEmail();
                        try {
                            $name = $loggedUser->getFirstName() . ' ' . $loggedUser->getLastName();
                        } catch(\Exception $e) {
                            $name = explode(' ', strstr($email, '@', true));
                        }
                    } else {
                        $form = $this->createForm(TicketForm::class, $ticket, [
                            'container' => $this->container,
                            'entity_manager' => $em,
                        ]);
                        $email = $request->request->get('from');
                        $name = explode(' ', $request->request->get('name'));
                    }

                    if($request->request->all())
                        $form->submit($request->request->all());
                    
                    // extract($this->get('customfield.service')->customFieldsValidation($request, 'customer'));
                    // if(!empty($errorFlashMessage)) {
                    //     $this->addFlash('warning', $errorFlashMessage);
                    // }

                    if ($form->isValid() && !count($formErrors) && !$error) {
                        $data = array(
                            'from' => $email, //email
                            'subject' => $request->request->get('subject'),
                            'reply' => $request->request->get('reply'),
                            'firstName' => $name[0],
                            'lastName' => isset($name[1]) ? $name[1] : '',
                            'role' => 4
                        );

                        $em = $this->getDoctrine()->getManager();
                        $data['type'] = $em->getRepository('UVDeskCoreBundle:TicketType')->find($request->request->get('type'));

                        if(!is_object($data['customer'] = $this->container->get('security.token_storage')->getToken()->getUser()) == "anon.") {
                            $customerEmail = $params['email'] = $request->request->get('from');
                            $customer = $em->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email' => $customerEmail));
                            $params['flag'] = (!$customer) ? 1 : 0;

                            $data['firstName'] = current($nameDetails = explode(' ', $request->request->get('name')));
                            $data['fullname'] = $request->request->get('name');
                            $data['lastName'] = ($data['firstName'] != end($nameDetails)) ? end($nameDetails) : " ";
                            $data['from'] = $customerEmail;
                            $data['role'] = 4;
                            $data['customer'] = $this->get('user.service')->getUserDetails($data);
                        } else {
                            $userDetail = $em->getRepository('UVDeskCoreBundle:User')->find($data['customer']->getId());
                            $data['email'] = $customerEmail = $data['customer']->getEmail();

                            $nameCollection = [$userDetail->getFirstName(), $userDetail->getLastName()];
                            $name = implode(' ', $nameCollection);
                            $data['fullname'] = $name;
                        }
                        $data['user'] = $data['customer'];
                        $data['subject'] = $request->request->get('subject');
                        $data['source'] = 'website';
                        $data['threadType'] = 'create';
                        $data['userType'] = 'customer';
                        $data['message'] = htmlentities($data['reply']);
                        $data['createdBy'] = $customerEmail;

                        if(!empty($request->server->get("HTTP_CF_CONNECTING_IP") )) {
                            $data['ipAddress'] = $request->server->get("HTTP_CF_CONNECTING_IP");
                            if(!empty($request->server->get("HTTP_CF_IPCOUNTRY"))) {
                                $data['ipAddress'] .= '(' . $request->server->get("HTTP_CF_IPCOUNTRY") . ')';
                            }
                        }

                        $thread = $this->get('ticket.service')->createTicketBase($data);
                        if($thread) {
                            $ticket = $thread->getTicket();
                            if($request->request->get('customFields') || $request->files->get('customFields'))
                                $this->get('ticket.service')->addTicketCustomFields($ticket, $request->request->get('customFields'), $request->files->get('customFields'));

                            // $this->get('event.manager')->trigger([
                            //         'event' => 'ticket.created',
                            //         'entity' => $ticket,
                            //         'user' => $thread->getUser(),
                            //         'userType' => 'customer'
                            //     ]);
                            $request->getSession()->getFlashBag()->set('success', $this->get('translator')->trans('Success ! Ticket has been created successfully.'));
                        } else {
                            $request->getSession()->getFlashBag()->set('warning', $this->get('translator')->trans('Warning ! Can not create ticket, invalid details.'));
                        }

                        return $this->redirect($this->generateUrl('helpdesk_customer_create_ticket'));
                    } else {
                        $errors = $this->getFormErrors($form);
                        $errors = array_merge($errors, $formErrors);
                    }
                } else {
                    $this->addFlash(
                        'warning',
                        $this->get('translator')->trans("Warning ! Post size can not exceed 25MB")
                    );
                }
            }

            if(isset($errors) && count($errors)) {
                $this->addFlash(
                    'warning',
                    key($errors) . ': ' . reset($errors)
                );
            }
        }

        $breadcrumbs = [
            [
                'label' => $this->get('translator')->trans('Support Center'),
                'url' => $this->generateUrl('helpdesk_knowledgebase')
            ],
            [
                'label' => $this->get('translator')->trans("Create Ticket Request"),
                'url' => '#'
            ],
        ];

        return $this->render('@UVDeskSupportCenter/Knowledgebase/ticket.html.twig',
            array(
                'formErrors' => $formErrors,
                'errors' => json_encode($errors),
                'customFieldsValues' => $request->request->get('customFields'),
                'breadcrumbs' => $breadcrumbs,
                'post' => $post
            )
        );
    }

    public function ticketList(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ticketRepo = $em->getRepository('UVDeskSupportCenterBundle:Wicket');

        $currentUser = $this->get('security.token_storage')->getToken()->getUser();
        if(!$currentUser || $currentUser == "anon.") {
            //throw error
        }
        
        $tickets = $ticketRepo->getAllCustomerTickets($currentUser);
        
        return $this->render('@UVDeskSupportCenter/Knowledgebase/ticketList.html.twig', array(
            'ticketList' => $tickets,
        ));
    }

    public function saveReply(int $id, Request $request)
    {
        $this->isWebsiteActive();

        $data = $request->request->all();
        $ticket = $this->getDoctrine()->getRepository('UVDeskCoreBundle:Ticket')->find($id);

        if($_POST) {
            if(str_replace(' ','',str_replace('&nbsp;','',trim(strip_tags($data['message'], '<img>')))) != "") {
                if($this->get('file.service')->validateAttachmentsSize($request->files->get('attachments'))) {

                    if(!$ticket)
                        $this->noResultFound();
                    $data['ticket'] = $ticket;
                    $data['user'] = $this->get('user.service')->getCurrentUser();

                    $userDetail = $this->get('user.service')->getCustomerPartialDetailById($data['user']->getId());
                    $data['fullname'] = $userDetail['name'];

                    $data['userType'] = 'customer';
                    $data['source'] = 'website';
                    $data['createdBy'] = $userDetail['email'];

                    $thread = $this->get('ticket.service')->createThread($ticket, $data);
                    // $this->get('ticket.service')->removeDraft($data);
                    // $this->get('event.manager')->trigger([
                    //             'event' => 'ticket.reply.added',
                    //             'entity' => $thread->getTicket(),
                    //             'targetEntity' => $thread,
                    //             'userType' => 'customer',
                    //         ]);

                    $em = $this->getDoctrine()->getManager();
                    $status = $em->getRepository('UVDeskCoreBundle:TicketStatus')->find($data['status']);
                    if($status) {
                        $flag = 0;
                        if($ticket->getStatus() != $status) {
                            // $notePlaceholders = $this->get('ticket.service')->getNotePlaceholderValues($ticket->getStatus()->getName(),$status->getName(),'status');
                            $flag = 1;
                        }

                        $ticket->setStatus($status);
                        $em->persist($ticket);
                        $em->flush();
                      }

                    $this->addFlash(
                        'success',
                        $this->get('translator')->trans("Success ! Reply added successfully.")
                    );
                } else {
                    $this->addFlash(
                        'warning',
                        $this->get('translator')->trans("Warning ! Files size can not exceed %size% MB",
                            [
                                "%size%" => $this->container->getParameter('max_upload_size')
                            ])
                    );
                }
            } else {
                $this->addFlash(
                    'warning',
                    $this->get('translator')->trans("Warning ! Reply field can not be blank.")
                );
            }
        } else {
            $this->addFlash(
                'warning',
                $this->get('translator')->trans("Warning ! Post size can not exceed 25MB")
            );
        }

        return $this->redirect($this->generateUrl('helpdesk_customer_ticket',array(
            'id' => $ticket->getId()
        )));
    }

    public function tickets(Request $request)
    {
        $this->isWebsiteActive();

        return $this->render('@UVDeskSupportCenter/Knowledgebase/ticketList.html.twig',
            array(
                'searchDisable' => true
            )
        );
    }

    /**
     * ticketListXhrAction "Filter and sort ticket collection on ajax request"
     * @param Object $request "HTTP Request object"
     * @return JSON "JSON response"
     */
    public function ticketListXhr(Request $request)
    {
        $this->isWebsiteActive();

        $json = array();
        if($request->isXmlHttpRequest()) {
            $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:Ticket');
    
            $json = $repository->getAllTickets($request->query, $this->container);
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }


    /**
     * threadListXhrAction "Filter and sort user collection on ajx request"
     * @param Object $request "HTTP Request object"
     * @return JSON "JSON response"
     */
    public function threadListXhr(Request $request)
    {
        $this->isWebsiteActive();

        $json = array();
        if($request->isXmlHttpRequest()) {
            $ticket = $this->getDoctrine()->getRepository('UVDeskCoreBundle:Ticket')->find($request->attributes->get('id'));
            // $this->denyAccessUnlessGranted('FRONT_VIEW', $ticket);

            $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:Thread');
            $json = $repository->getAllCustomerThreads($request->attributes->get('id'),$request->query, $this->container);
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function ticketView(int $id, Request $request)
    {
        $this->isWebsiteActive();

        $em = $this->getDoctrine()->getManager();

        $ticket = $em->getRepository('UVDeskCoreBundle:Ticket')->find($id);

        if(!$ticket)
            $this->noResultFound();
        
        $ticket->setIsCustomerViewed(1);
        $em->persist($ticket);
        $em->flush();
        
        $twigResponse = [
            'ticket' => $ticket,
            'searchDisable' => true,
        ];

        return $this->render('@UVDeskSupportCenter/Knowledgebase/ticketView.html.twig', $twigResponse);
    }
}
