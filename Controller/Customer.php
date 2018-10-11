<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Security;

Class Customer extends Controller
{
    protected function redirectUserToLogin()
    {
        $authChecker = $this->container->get('security.authorization_checker');
        if($authChecker->isGranted('ROLE_CUSTOMER'))
            return true;
    }

    protected function isWebsiteActive()
    {
        $error = false;
        $this->getCompany(true) ? ($error = !$this->getCompany(true)->getStatus()) : ($error = true);

        if($error)
            $this->noResultFound();
    }

    protected function noResultFound()
    {
        throw new NotFoundHttpException('Permission Denied !');
    }

    protected function encodePassword(User $user, $plainPassword)
    {
        $encoder = $this->container->get('security.encoder_factory')
                   ->getEncoder($user);

        return $encoder->encodePassword($plainPassword, $user->getSalt());
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

        return $this->render('@UVDeskSupportCenter/Front/login.html.twig', [
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

    public function forgotPasswordAction()
    {
        dump("sdf");
        die;
    }
}