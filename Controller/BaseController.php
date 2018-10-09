<?php
// src/Webkul/UserBundle/Controller/BaseController.php
namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

const C_PLAN_EXPIRED = 601;
const C_AUTH_VIOLATION = 601;
const U_AUTH_VIOLATION = 603;

class BaseController extends Controller
{   
    protected function getFormErrors($form) {
        $errorContext = array();
        foreach ($form->getErrors(true) as $key => $error) {
            $errorContext[$error->getOrigin()->getName()] = $error->getMessage();
        }

        return $errorContext;
    }

    /**
     * If customer is playing with url and no result is found then what will happen
     * @return 
     */
    protected function noResultFound()
    {
        throw new NotFoundHttpException($this->translate('Not Found!'));
    }

    protected function isAuthorized($role = "") {
        if($role != "") {
            $requestMethod = $this->container->get('request_stack')->getCurrentRequest()->getMethod();
            // dump($this->container->get('request_stack')->getCurrentRequest());die;
           
            if(!$this->get('user.service')->checkPermission($role)) {               
                    $exception = new \Exception($this->translate('Warning ! You are not authorized to perform this action.'), U_AUTH_VIOLATION);
            }

            if (!empty($exception))
                throw new AccessDeniedException($exception->getMessage(), $exception);
        }
        return true;  
    }

    protected function applicationAccessGranted($applicationName = 'apps') 
    {
        try {
            if (!$this->isAuthorized($applicationName))
                return false;
        } catch (AccessDeniedException $e) {
            return false;
        }

        return true;
    }

    protected function applicationAuthorizedCompany($applicationName = null)
    {
        try {
            if (!$this->isAuthorized($applicationName))
                return false;
        } catch (AccessDeniedException $e) {
            if ($e->getPrevious()->getCode() == C_PLAN_EXPIRED || $e->getPrevious()->getCode() == C_AUTH_VIOLATION)
                return false;
            else
                return true;
        }

        return true;
    }

    protected function redirectLoginUserAction()
    {
        $securityContext = $this->container->get('security.context');

        if($securityContext->isGranted('ROLE_AGENT'))
            return true;
    }

    protected function getCurrentUser() {
        return $this->container->get('security.context')->getToken()->getUser();   
    }

    protected function getCurrentCompany() {
        return $this->container->get('user.service')->getCurrentCompany();
    }

    public function getListItems($request) {
        $list = array();
        $userService = $this->get('user.service');
        $route = $request->attributes->get('_route');
        $securityContext = $this->container->get('security.context');
        
        // 1. User Block
        // 1.1 Teams
        if ($userService->checkPermission('ROLE_AGENT_MANAGE_SUB_GROUP')) {
            $class = in_array($route, array('subgroup_list', 'subgroup_add_action', 'subgroup_edit_action')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Teams'), 'link' => $this->generateUrl('subgroup_list'), 'class' => $class];
        }
        // 1.2 Groups
        if ($userService->checkPermission('ROLE_AGENT_MANAGE_GROUP')) {
            $class = in_array($route, array('group_list', 'edit_group')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Groups'), 'link' => $this->generateUrl('group_list'), 'class' => $class];
        }
        // 1.3 Agents
        if ($userService->checkPermission('ROLE_AGENT_MANAGE_AGENT')) {
            $class = in_array($route, array('user_list', 'edit_user')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Agents'), 'link' => $this->generateUrl('user_list'), 'class' => $class];
        }
        // 1.4 Customers
        if ($userService->checkPermission('ROLE_AGENT_MANAGE_CUSTOMER')) {
            $class = in_array($route, array('customer_list', 'edit_customer')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Customers'), 'link' => $this->generateUrl('customer_list'), 'class' => $class];
        }
        // 1.5 Privileges
        if ($userService->checkPermission('ROLE_AGENT_MANAGE_AGENT_PRIVILEGE')) {
            $class = in_array($route, array('agent_privileges_list', 'edit_agent_privilege')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Privileges'), 'link' => $this->generateUrl('agent_privileges_list'), 'class' => $class];
        }

        // 2. Workflow Block
        // 2.1 Mailbox
        if ($securityContext->isGranted('ROLE_ADMIN')) {
            $class = in_array($route, array('company_mailBoxes')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Mailbox'), 'link' => $this->generateUrl('company_mailBoxes'), 'class' => $class];
        }
        // 2.2 Workflows
        if($userService->checkPermission('ROLE_AGENT_MANAGE_WORKFLOW_AUTOMATIC') || $userService->checkPermission('ROLE_AGENT_MANAGE_WORKFLOW_MANUAL')) {
            $class = in_array($route, array('workflows_action', 'workflows_addaction', 'workflows_editaction')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Workflows'), 'link' => $this->generateUrl('workflows_action'), 'class' => $class];
        }
        // 2.3 Block Spam
        if ($securityContext->isGranted('ROLE_ADMIN')) {
            $class = in_array($route, array('company_spam')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Block Spam'), 'link' => $this->generateUrl('company_spam'), 'class' => $class];
        }
        // 2.4 Saved Replies
        if($userService->checkCompanyPermission('saved_replies')) {
            $class = in_array($route, array('saved_replies_action', 'saved_replies_addaction', 'saved_replies_editaction')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Saved Replies'), 'link' => $this->generateUrl('saved_replies_action'), 'class' => $class];
        }
        // 2.5 Email Settings
        if ($userService->checkPermission('email_settings')) {
            $class = in_array($route, array('email_setting')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Email Settings'), 'link' => $this->generateUrl('email_setting'), 'class' => $class];
        }
        // 2.6 Email Templates
        if($userService->checkPermission('ROLE_AGENT_MANAGE_EMAIL_TEMPLATE')) {
            $class = in_array($route, array('email_templates_action', 'email_templates_addaction', 'email_templates_editaction')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Email Templates'), 'link' => $this->generateUrl('email_templates_action'), 'class' => $class];
        }

        // 3. Tickets Block
        // 3.1 Tags
        if($userService->checkPermission('ROLE_AGENT_MANAGE_TAG')) {
            $class = in_array($route, array('tag_list')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Tags'), 'link' => $this->generateUrl('tag_list'), 'class' => $class];
        }
        // 3.2 Types
        if($userService->checkPermission('ROLE_AGENT_MANAGE_TICKET_TYPE')) {
            $class = in_array($route, array('ticket_type_list')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Types'), 'link' => $this->generateUrl('ticket_type_list'), 'class' => $class];
        }
        // 3.3 Custom Fields
        if($userService->checkPermission('ROLE_AGENT_MANAGE_CUSTOM_FIELD')) {
            $class = in_array($route, array('custom_fields_action', 'custom_fields_addaction', 'custom_fields_editaction')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Custom Fields'), 'link' => $this->generateUrl('custom_fields_action'), 'class' => $class];
        }
        // 3.4 Custom Fields Log
        if ($securityContext->isGranted('ROLE_ADMIN')) {
            $class = in_array($route, array('custom_fields_log')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Custom Fields Log'), 'link' => $this->generateUrl('custom_fields_log'), 'class' => $class];
        }

        // 4. Company Block
        // 4.1 Business Hours
        if ($securityContext->isGranted('ROLE_ADMIN')) {
            $class = in_array($route, array('company_businesshours')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Business Hours'), 'link' => $this->generateUrl('company_businesshours'), 'class' => $class];
        }
        // 4.2 Company Settings
        if ($securityContext->isGranted('ROLE_ADMIN')) {
            $class = in_array($route, array('company_details')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Company Settings'), 'link' => $this->generateUrl('company_details'), 'class' => $class];
        }
        // 4.3 Theme and Customization
        if ($securityContext->isGranted('ROLE_ADMIN')) {
            $class = in_array($route, array('company_theme')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Theme and Customization'), 'link' => $this->generateUrl('company_theme'), 'class' => $class];
        }

        // 5. Data Migration
        // 5.1 Import Data
        if($userService->checkPermission('import_data')) {
            $class = in_array($route, array('import_data')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Import Data'), 'link' => $this->generateUrl('import_data'), 'class' => $class];
        }
        // 5.2 Export Data
        if($userService->checkPermission('export_data')) {
            $class = in_array($route, array('export_data')) ? 'active' : '';
            $list[] = ['title' => $this->translate('Export Data'), 'link' => $this->generateUrl('export_data'), 'class' => $class];
        }

        /*if($securityContext->isGranted('ROLE_ADMIN')) {
            $class = $route == "company_mailBoxes" ? "active" : "";
            array_push($list, array('title' => $this->translate('Mailbox'),'link' => $this->generateUrl('company_mailBoxes'),'class' => $class));

            $class = $route == "company_theme" ? "active" : "";
            array_push($list, array('title' => $this->translate('Theme and Customization'),'link' => $this->generateUrl('company_theme') ,'class' => $class));

            $class = $route == "company_details" ? "active" : "";
            array_push($list, array('title' => $this->translate('Company Setting'),'link' => $this->generateUrl('company_details'),'class' => $class));

            $class = $route == "company_spam" ? "active" : "";
            array_push($list, array('title' => $this->translate('Block Spam'),'link' => $this->generateUrl('company_spam'),'class'=>$class));

            $class = $route == "company_businesshours" ? "active" : "";
            array_push($list, array('title' => $this->translate('Business Hours'),'link'=>$this->generateUrl('company_businesshours'),'class'=>$class));

            $class = $route == "custom_fields_log" ? "active" : "";
            array_push($list, array('title' => $this->translate('Ticket Custom Field Log'),'link' => $this->generateUrl('custom_fields_log') ,'class' => $class));
        }
        
        if($userService->checkPermission('ROLE_AGENT_MANAGE_EMAIL_TEMPLATE')) {
            $class = $route == "email_templates_action" || $route == "email_templates_addaction" || $route == "email_templates_editaction" ? "active" : "";
            array_push($list, array('title' => $this->translate('Email Templates'),'link' => $this->generateUrl('email_templates_action') ,'class' => $class));
        }

        if($userService->checkPermission('ROLE_AGENT_MANAGE_CUSTOMER')) {
            $class = $route == "customer_list" || $route == "edit_customer" ? "active" : "";
            array_push($list, array('title' => $this->translate('Customers'),'link' => $this->generateUrl('customer_list'),'class' => $class));
        }

        if($userService->checkPermission('ROLE_AGENT_MANAGE_GROUP')) {
            $class = $route == "group_list" || $route == "edit_group" ? "active" : "";
            array_push($list, array('title' => $this->translate('Groups'),'link' => $this->generateUrl('group_list'),'class' => $class));
        }

        if($userService->checkPermission('ROLE_AGENT_MANAGE_SUB_GROUP')) {
            $class = $route == "subgroup_list" || $route == "subgroup_add_action" || $route == "subgroup_edit_action" ? "active" : "";
            array_push($list, array('title' => $this->translate('Teams'),'link' => $this->generateUrl('subgroup_list'),'class' => $class));
        }

        if($userService->checkPermission('ROLE_AGENT_MANAGE_AGENT')) {
            $class = $route == "user_list" || $route == "edit_user" ? "active" : "";
            array_push($list, array('title' => $this->translate('Agents'),'link' => $this->generateUrl('user_list'),'class' => $class));
        }

        if($userService->checkPermission('ROLE_AGENT_MANAGE_AGENT_PRIVILEGE')) {
            $class = $route == "agent_privileges_list" || $route == "edit_agent_privilege" ? "active" : "";
            array_push($list, array('title' => $this->translate('Agent Privileges'),'link' => $this->generateUrl('agent_privileges_list'),'class' => $class));
        }

        if($userService->checkPermission('ROLE_AGENT_MANAGE_TICKET_TYPE')) {
            $class = $route == "ticket_type_list" ? "active" : "";
            array_push($list, array('title' => $this->translate('Ticket Types'),'link' => $this->generateUrl('ticket_type_list'),'class' => $class));
        }

        if($userService->checkPermission('ROLE_AGENT_MANAGE_CUSTOM_FIELD')) {
            $class = $route == "custom_fields_action" || $route == "custom_fields_addaction" || $route == "custom_fields_editaction" ? "active" : "";
            array_push($list, array('title' => $this->translate('Ticket Custom Fields'),'link' => $this->generateUrl('custom_fields_action') ,'class' => $class));
        }

        if($userService->checkCompanyPermission('saved_replies')) {
            $class = $route == "saved_replies_action" || $route == "saved_replies_addaction" || $route == "saved_replies_editaction" ? "active" : "";
            array_push($list, array('title' => $this->translate('Saved Replies'),'link' => $this->generateUrl('saved_replies_action') ,'class' => $class));
        }

        if($userService->checkPermission('ROLE_AGENT_MANAGE_WORKFLOW_AUTOMATIC') || $userService->checkPermission('ROLE_AGENT_MANAGE_WORKFLOW_MANUAL')) {
            $class = $route == "workflows_action" || $route == "workflows_addaction" || $route == "workflows_editaction" ? "active" : "";
            array_push($list, array('title' => $this->translate('Workflow'),'link' => $this->generateUrl('workflows_action') ,'class' => $class));
        }
        
        if($userService->checkPermission('ROLE_AGENT_MANAGE_TAG')) {
            $class = $route == "tag_list" ? "active" : "";
            array_push($list, array('title' => $this->translate('Tags'),'link' => $this->generateUrl('tag_list'),'class' => $class));
        }

        if($userService->checkPermission('export_data')) {
            $class = $route == "export_data" ? "active" : "";
            array_push($list, array('title' => $this->translate('Export Data'),'link' => $this->generateUrl('export_data'),'class' => $class));
        }

        if($userService->checkPermission('import_data')) {
            $class = $route == "import_data" ? "active" : "";
            array_push($list, array('title' => $this->translate('Import data'),'link' => $this->generateUrl('import_data'),'class' => $class));
        }
        
        if($userService->checkPermission('email_settings')) {
            $class = $route == "email_setting" ? "active" : "";
            array_push($list, array('title' => $this->translate('Email Setting'),'link' => $this->generateUrl('email_setting'),'class' => $class));
        }*/
        
        return $list;
    }

    public function getRightSidebarInfoItems($request) {
        $route = $request->attributes->get('_route');
        $em = $this->getDoctrine()->getManager();
        $informations = $em->getRepository('WebkulAdminBundle:SidebarInformation')->findByRoute($route, array('sortOrder' => 'ASC'));
        return $informations;
    }

    public function translate($string,$params = array())
    {
        return $this->get('translator')->trans($string,$params);
    }

    public function objectSerializer($object,$ignoredFields = array()) {
        $encoder = new JsonEncoder();
        
        $normalizer = new GetSetMethodNormalizer(null);
        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getId();
        });
        
        if(count($ignoredFields))
            $normalizer->setIgnoredAttributes($ignoredFields);
        else
            $normalizer->setIgnoredAttributes(array('password','users','createdAt','updatedAt','salt','token','facebook','twitter','company','groups','tmpMarks','path','profilePic','contactNumber','roles','userName','jobTitle','privileges','tickets'));
        $serializer = new Serializer(array($normalizer), array($encoder));
        return $userContext = $serializer->serialize($object, 'json');
    }

    protected function symfony_http_build_query(array $query) {
        $query['page'] = "replacePage";
        $params = array();
        foreach ($query as $key => $value) {
            if (!isset($value)) {
                $params[] = $key;
            } else {
                $params[] = $key . '/' . str_replace('%2F', '/', rawurlencode($value));
            }
        }
        return implode('/', $params);
    }

    /**
     * Function is Entity Manger sort form
     * @param  string $entity   Entity Name
     * @param  string $callFunction Calling Function from entity
     * @param  depends $args    it will be calling function arguments
     * @return object           result
     */
    protected function getEntityManagerResult($entity, $callFunction, $args = false)
    {
        return $this->getDoctrine()
                    ->getRepository($entity)
                    ->$callFunction($args);
    }

    /**
     * Search if passed keys aren't empty in given object, basically to validatate form entity type
     * @param  Object $object entity object
     * @param  array  $key    Array of keys, which we will use in object
     * @return array          validated errors or []
     */
    protected function customBlankValidation($object, $key = [])
    {   
        $errors = [];
        foreach ($key as $value) {
            if(!count($object->{'get'.ucfirst($value)}()))
                $errors[$value] = $this->translate("%value% value should not be blank.", array("%value%" => ucfirst($value)));
        }
        return $errors;
    }

    public function sendMail($data)
    {
        $mailer = $this->get('mailer');
        $message = $mailer->createMessage()
                        ->setSubject(!empty($data['emailChangeMessage']) ? $this->translate("Email change confirmation for %name%",array("%name%" => $data['name'])) : $this->translate("Password Reset Confirmation for %name%",array("%name%" => $data['name']))  )
                        ->setFrom(array("support@uvdesk.in" => "UVdesk"))
                        ->setTo($data['email'])
                        ->setBody( 
                            $this->render('WebkulAdminBundle:Default\MailTemplates:passResetConfirmation.html.twig', $data)->getContent(),
                            'text/html'
                        );   
        try {
            $mailer->send($message);
        } catch(\Exception $e) {
        }
    }

}
