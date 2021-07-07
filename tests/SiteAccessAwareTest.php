<?php

namespace App\Tests;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\Core\Repository\SiteAccessAware\Repository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SiteAccessAwareTest extends KernelTestCase
{
    /** @var ConfigResolver */
    private $configResolver;

    public function setup(): void
    {
        self::bootKernel();
        $this->configResolver = self::$kernel->getContainer()->get('ezpublish.config.resolver');
    }

    public function testSiteAccess()
    {
        //$wantedSiteAccess = 'site';
        $wantedSiteAccess = 'admin';

        self::$kernel->getContainer()->set('ezpublish.siteaccess', new SiteAccess($wantedSiteAccess, 'phpunit'));

        self::assertEquals("$wantedSiteAccess value", $this->configResolver->getParameter('unit_test'));

        /** @var Repository $repository */
        $repository = self::$kernel->getContainer()->get('ezpublish.api.repository');

        // Shows that the default connection is used
        $rootLocation = $repository->getLocationService()->loadLocation(2);
        self::assertNotNull($rootLocation);
        self::assertEquals(2, $rootLocation->id);
        self::assertEquals('Home', $rootLocation->getContentInfo()->name);
    }
}
