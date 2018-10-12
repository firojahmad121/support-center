<?php

namespace Webkul\UVDesk\SupportCenterBundle\Workstation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Form\BrandingGeneral;
use Webkul\UVDesk\SupportCenterBundle\Entity\Website;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Branding extends Controller
{  
    public function theme(Request $request)
    {
        $errors = [];
        $em = $this->getDoctrine()->getManager();
        $settingType = $request->attributes->get('type');
        $userService = $this->container->get('user.service');
        $website = $em->getRepository('UVDeskCoreBundle:Website')->findOneBy(['code'=>"website_branding"]);
        $configuration = $em->getRepository('UVDeskSupportCenterBundle:KnowledgebaseConfiguration')->findOneBy(['website' => $website->getId(), 'isActive' => 1]);

        if ($request->getMethod() == 'POST') {
            $isValid = 0;
            $params = $request->request->all();
            $parmsFile = ($request->files->get('website'));

            switch($settingType) {
                case "general":
                    $website->setName($params['website']['name']);
                    $status = array_key_exists("status",$params['website']) ? 1 : 0;
                    $website->setIsActive($status);
                    if(isset($parmsFile['logo'])){
                        $fileName  = $this->container->get('fileupload.service')->upload($parmsFile['logo']);
                        $website->setLogo($fileName);
                    }
                    $em->persist($website);
                    $em->flush(); 

                    $configuration->setBrandColor($params['website']['brandColor']);
                    $em->persist($configuration);
                    $em->flush();
                    break;
                case "knowledgebase":
                // dump($params);die;
                    $configuration->setPageBackgroundColor($params['website']['pageBackgroundColor']);
                    $configuration->setHeaderBackgroundColor($params['website']['headerBackgroundColor']); 

                    $configuration->setLinkColor($params['website']['linkColor']);  
                    $configuration->setLinkHoverColor($params['website']['linkHoverColor']);  
                    $configuration->setArticleTextColor($params['website']['articleTextColor']);  
                    $configuration->setSiteDescription($params['website']['siteDescription']);  
                    $configuration->setBannerBackgroundColor($params['website']['bannerBackgroundColor']);  
                    $configuration->setNavTextColor($params['website']['navTextColor']);
                    $configuration->setNavActiveColor($params['website']['navActiveColor']);


                    $removeCustomerLoginButton = array_key_exists("removeCustomerLoginButton",$params['website']) ? $params['website']['removeCustomerLoginButton'] : 0;
                    $removeBrandingContent = array_key_exists("removeBrandingContent",$params['website']) ? $params['website']['removeBrandingContent'] : 0;
                    $disableCustomerLogin = array_key_exists("disableCustomerLogin",$params['website']) ? $params['website']['disableCustomerLogin'] : 0;
                    

                    $configuration->setRemoveCustomerLoginButton($removeCustomerLoginButton);
                    $configuration->setRemoveBrandingContent($removeBrandingContent);
                    $configuration->setDisableCustomerLogin($disableCustomerLogin);

                    $ticketCreateOption = array_key_exists('ticketCreateOption',$params['website']) ? $params['website']['ticketCreateOption'] : 0;
                    $loginRequiredToCreate = array_key_exists('loginRequiredToCreate',$params['website']) ? $params['website']['loginRequiredToCreate'] : 0;
                    $configuration->setTicketCreateOption($ticketCreateOption);                
                    $configuration->setLoginRequiredToCreate($loginRequiredToCreate);
                    $configuration->setUpdatedAt(new \DateTime());
                    $em->persist($configuration);
                    $em->flush();
                    break;
                case "seo":
                    $configuration->setMetaDescription($params['metaDescription']);  
                    $configuration->setMetaKeywords($params['metaKeywords']);  
                    $configuration->setUpdatedAt(new \DateTime());
                    $em->persist($configuration);
                    $em->flush();
                    break;
                case "links":
                    $footerLinks=[];
                    $headerLinks=[];
                    $headerLinks = $params['headerLinks'];                    
                    if(count($headerLinks)>0){
                        foreach ($headerLinks as $key => $link) {
                            if($link['label'] == '' || $link['url'] == '' || !filter_var($link['url'], FILTER_VALIDATE_URL)) {
                                
                                unset($headerLinks[$key]);
                            }
                        }
                    } 
                    $footerLinks = $params['footerLinks'];
                    if(count($footerLinks)>0){
                        foreach ($footerLinks as $key => $link) {
                            if($link['label'] == '' || $link['url'] == '' || !filter_var($link['url'], FILTER_VALIDATE_URL)) {
                                unset($footerLinks[$key]);
                            }
                        }
                    }

                    $configuration->setHeaderLinks($headerLinks);
                    $configuration->setFooterLinks($footerLinks);
                    $em->persist($configuration);
                    $em->flush();
                    break;
                case "broadcasting":
                    $isActive = array_key_exists('isActive',$params['broadcasting']) ? [] : ["isActive"=>0];
                    $broadcast = json_encode(array_merge($params['broadcasting'],$isActive));
                    $configuration->setBroadcastMessage($broadcast); 
                    $configuration->setUpdatedAt(new \DateTime());
                    $em->persist($configuration);
                    $em->flush();
                    break;
                case 'advanced':
                    $configuration->setCustomCSS($request->request->get('customCSS'));
                    $configuration->setScript($request->request->get('script'));
                    $em->persist($configuration);
                    $em->flush();
                    break;
                default:
                    break;
            }
        }
        // dump($configuration);die;/
        return $this->render('@UVDeskSupportCenter/Staff/branding.html.twig', [
            'website' => $website,
            'type' => $settingType,
            'configuration' => $configuration,
            'broadcast' => json_decode($configuration->getBroadcastMessage()),
            'errors' => json_encode($errors),
        ]);
    }

    public function spam(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $website = $em->getRepository('UVDeskCoreBundle:Website')->findOneBy(['code'=>"website_branding"]);
        if(!$website) {
            // return not found
        }
        $configuration = $em->getRepository('UVDeskSupportCenterBundle:KnowledgebaseConfiguration')->findOneBy(['website' => $website->getId(), 'isActive' => 1]);
        $params = $request->request->all();

        
        if ($request->getMethod() == 'POST') {
            $configuration->setWhiteList($request->request->get('whiteList'));
            $configuration->setBlackList($request->request->get('blackList'));
            $em->persist($configuration);
            $em->flush();

            $this->addFlash('success', 'Spam setting saved successfully.');

            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_spam'));
        }
        
        return $this->render('@UVDeskSupportCenter/Staff/spam.html.twig', [
            'whitelist'=>$configuration->getWhiteList(),
            'blacklist'=>$configuration->getBlackList(),
        ]);
    }

}