<?php

namespace Webkul\UVDesk\SupportCenterBundle\Knowledgebase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Security;
use Webkul\UVDesk\CoreBundle\Entity\User;

Class Customer extends Controller
{
    protected function redirectUserToLogin()
    {
        $authChecker = $this->container->get('security.authorization_checker');
        if($authChecker->isGranted('ROLE_CUSTOMER'))
            return true;
    }


    protected function noResultFound()
    {
        throw new NotFoundHttpException('Permission Denied !');
    }

    protected function encodePassword(User $user, $plainPassword)
    {
      return  $encodedPassword = $this->container->get('security.password_encoder')->encodePassword($user, $plainPassword);
    }
    protected function isLoginDisabled()
    {
        $error = false;

        // if(method_exists($website, 'getDisableCustomerLogin') && $website->getDisableCustomerLogin()) {
            // return true;
        // }
        return false;
    }

    public function login(Request $request)
    {
        if($this->redirectUserToLogin())
            return $this->redirect($this->generateUrl('helpdesk_customer_ticket_collection')); // Replace with Dashboard route

        /** check disabled customer login **/
        if($this->isLoginDisabled()) {
            $this->addFlash('warning', $this->get('translator')->trans('Warning ! Customer Login disabled by admin.') );
            return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));
        }

        $session = $request->getSession();

        $error = $session->get(Security::AUTHENTICATION_ERROR);
        $session->remove(Security::AUTHENTICATION_ERROR);

        return $this->render('@UVDeskSupportCenter/Knowledgebase/login.html.twig', [
                'searchDisable' => true,
                'last_username' => $session->get(Security::LAST_USERNAME),
                'error'         => $error,
                'breadcrumbs' => [
                    [
                        'label' => $this->get('translator')->trans('Support Center'),
                        'url' => $this->generateUrl('helpdesk_knowledgebase')
                    ], [
                        'label' => $this->get('translator')->trans('Sign In'),
                        'url' => '#'
                    ]
                ]
            ]);
    }

    public function forgotPassword(Request $request)
    {
      
        if($this->isLoginDisabled()) {
            $this->addFlash('warning','Warning ! Customer Login disabled by admin.');
            return $this->redirect($this->generateUrl('webkul_support_center_front_solutions'));
        }

        $errors = [];
        if($request->getMethod() == 'POST') {
            $entityManager = $this->getDoctrine()->getManager();
            $user = new User();
            $data = $request->request->all();
            $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:User');
            $user = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email' => $data['email']));
            
            if($user) { 
                   
                    $key = time();
                    $request->getSession()->getFlashBag()->set(
                            'success',$this->get('translator')->trans('Please check your mail for password update.')
                        );
                return $this->redirect($this->generateUrl('helpdesk_customer_account_validation')."/".$data['email']."/".$key);

                    // return $this->redirect($this->generateUrl('webkul_support_center_front_forgot_password'));
                } else {
                    $request->getSession()->getFlashBag()->set('warning', $this->get('translator')->trans('This Email is not registered with us.'));
                }
            }
            else{
                $request->getSession()->getFlashBag()->set('warning', $this->get('translator')->trans('This Email is not registered with us.'));
                return $this->render('@UVDeskSupportCenter/Knowledgebase/forgotPassword.html.twig', [
                    'searchDisable' => true,
                    'errors' => json_encode($errors),
                    'breadcrumbs' => [
                        [
                            'label' => 'Support Center',
                            'url' => 'webkul_support_center_front_solutions'
                        ], [
                            'label' => 'Forgot Password',
                            'url' => '#'
                        ]
                    ]
                ]);
            } 
            return $this->render('@UVDeskSupportCenter/Knowledgebase/forgotPassword.html.twig', [
                'searchDisable' => true,
                'errors' => json_encode($errors),
                'breadcrumbs' => [
                    [
                        'label' => 'Support Center',
                        'url' => 'webkul_support_center_front_solutions'
                    ], [
                        'label' => 'Forgot Password',
                        'url' => '#'
                    ]
                ]
            ]);
        }

        public function AccountValidation(Request $request)
        {
           
            if($this->isLoginDisabled()) {
                $this->addFlash('warning','Warning ! Customer Login disabled by admin.');
                return $this->redirect($this->generateUrl('webkul_support_center_front_solutions'));
            }
            $errors = [];
           
            if($request->attributes->get('email') && $request->attributes->get('key'))
            {
                $entityManager = $this->getDoctrine()->getManager();
                $user = new User();
                $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:User');
                $user = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email' => $request->attributes->get('email')));
                
                if($user) 
                {
                    if($request->getMethod() == 'POST') 
                    {

                    $data = $request->request->all();
                    if($data['password']['first']===$data['password']['second'])
                    {
                        $user->setPassword($this->encodePassword($user, $data['password']['first']));
                        $entityManager->persist($user);
                        $entityManager->flush();            
                        $request->getSession()->getFlashBag()->set('success', 'Your password changed.');
                        return  $this->redirect($this->generateUrl('helpdesk_customer_login'));

                    } else {
                            $request->getSession()->getFlashBag()->set('warning', 'Password does not match.');
                           
                        }
                    }
                }

                 return $this->render('@UVDeskSupportCenter/Knowledgebase/resetPassword.html.twig', [
                        'searchDisable' => true,
                        'errors' => json_encode($errors),
                        'breadcrumbs' => [
                            [
                                'label' =>'Support Center',
                                'url' => 'webkul_support_center_front_solutions'
                            ], [
                                'label' => 'Account Validation',
                                'url' => '#'
                            ]
                        ]
                    ]);

            }
            else {
                $request->getSession()->getFlashBag()->set('warning','Warning! This request is not validated !! This request has been processed, already.');
                return $this->redirect($this->generateUrl('helpdesk_customer_login'));                
            }
             
        }
        
     
}