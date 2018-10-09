<?php

namespace Webkul\UVDesk\SupportCenterBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Webkul\UVDesk\SupportCenterBundle\Entity\Website;
use Webkul\UVDesk\SupportCenterBundle\Entity\KnowledgebaseConfiguration;

class BrandingFixtures extends Fixture
{
    private static $websiteSeed = [
        'name' => 'New Website',
        'code' => 'website_branding',
        'logo' => '#7E91F0',
        'favicon' => 'favicon',
        'is_active' => true,
    ];

    private static $websiteConfigurationSeed = [
        'status' => true,
        'brand_color' => '#7E91F0',
        'page_background_color' => '#FFFFFF',
        'header_background_color' => '#FFFFFF',
        'banner_background_color' => '#7085F4',
        'nav_text_color' => '#7085F4',
        'nav_active_color' => '#7085F4',
        'page_link_color' => '#7085F4',
        'page_link_hover_color' => '#7085F4',
        'article_text_color' => '#7085F4',
        'site_description' => 'Hi! how can i help you.',
        'broadcast_message' => null,
        'ticket_create_option' => true,
        'disable_customer_login' => false,
        'login_required_to_create' => true,
    ];

    public function load(ObjectManager $entityManager)
    {
        $website = $entityManager->getRepository('UVDeskSupportCenterBundle:Website')->findOneByCode('website_branding');
        
        if (empty($website)) {
            $website = new Website();
            $website->setName(self::$websiteSeed['name']);
            $website->setCode(self::$websiteSeed['code']);
            $website->setLogo(self::$websiteSeed['logo']);
            $website->setFavicon(self::$websiteSeed['favicon']);
            $website->setIsActive(self::$websiteSeed['is_active']);
            $website->setCreatedAt(new \DateTime());
            $website->setUpdatedAt(new \DateTime());

            $entityManager->persist($website);
            $entityManager->flush();
        }

        $websiteConfiguration = $entityManager->getRepository('UVDeskSupportCenterBundle:KnowledgebaseConfiguration')->findOneByWebsite($website);
        
        if (empty($websiteConfiguration)) {
            $websiteConfiguration = new KnowledgebaseConfiguration();

            $websiteConfiguration->setStatus(self::$websiteConfigurationSeed['status']);
            $websiteConfiguration->setBrandColor(self::$websiteConfigurationSeed['brand_color']);
            $websiteConfiguration->setPageBackgroundColor(self::$websiteConfigurationSeed['page_background_color']);
            $websiteConfiguration->setHeaderBackgroundColor(self::$websiteConfigurationSeed['page_background_color']);
            $websiteConfiguration->setBannerBackgroundColor(self::$websiteConfigurationSeed['banner_background_color']);
            $websiteConfiguration->setNavTextColor(self::$websiteConfigurationSeed['nav_text_color']);
            $websiteConfiguration->setNavActiveColor(self::$websiteConfigurationSeed['nav_active_color']);
            $websiteConfiguration->setLinkColor(self::$websiteConfigurationSeed['page_link_color']);
            $websiteConfiguration->setLinkHoverColor(self::$websiteConfigurationSeed['page_link_hover_color']);
            $websiteConfiguration->setArticleTextColor(self::$websiteConfigurationSeed['article_text_color']);
            $websiteConfiguration->setSiteDescription(self::$websiteConfigurationSeed['site_description']);
            $websiteConfiguration->setBroadcastMessage(self::$websiteConfigurationSeed['broadcast_message']);

            $websiteConfiguration->setTicketCreateOption(self::$websiteConfigurationSeed['ticket_create_option']);
            $websiteConfiguration->setDisableCustomerLogin(self::$websiteConfigurationSeed['disable_customer_login']);
            $websiteConfiguration->setIsActive(1);
            $websiteConfiguration->setCreatedAt(new \DateTime());
            $websiteConfiguration->setUpdatedAt(new \DateTime());
            $websiteConfiguration->setWebsite($website);

            $entityManager->persist($websiteConfiguration);
            $entityManager->flush();
        }
    }
}
