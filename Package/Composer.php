<?php

namespace Webkul\UVDesk\SupportCenterBundle\Package;

use Webkul\UVDesk\PackageManager\Composer\ComposerPackage;
use Webkul\UVDesk\PackageManager\Composer\ComposerPackageExtension;

class Composer extends ComposerPackageExtension
{
    public function loadConfiguration()
    {
        $composerPackage = new ComposerPackage(new UVDeskSupportCenterConfiguration());
        $composerPackage
            ->movePackageConfig('config/routes/uvdesk_support_center.yaml', 'Templates/routes.yaml')
            ->updateSecurityConfig('Templates/security-configs.yaml');
        
        return $composerPackage;
    }
}
