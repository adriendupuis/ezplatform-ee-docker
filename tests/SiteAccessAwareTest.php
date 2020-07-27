<?php

namespace App\Tests;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ChainConfigResolver;
use eZ\Publish\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use eZ\Publish\Core\MVC\Symfony\SiteAccess\SiteAccessService;
use eZ\Publish\Core\Repository\SiteAccessAware\Repository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SiteAccessAwareTest extends KernelTestCase
{
    /** @var SiteAccessService */
    private $siteAccessService;

    /** @var ChainConfigResolver */
    private $configResolver;

    public function setup(): void
    {
        self::bootKernel();
        $this->siteAccessService = self::$kernel->getContainer()->get('test_alias.ezpublish.siteaccess_service');
        $this->configResolver = self::$kernel->getContainer()->get('ezpublish.config.resolver');
    }

    public function testSiteAccess()
    {
        //$wantedSiteAccess = 'site';
        $wantedSiteAccess = 'admin';

        // Contrary to 2.5: The "ezpublish.siteaccess" service is private, you cannot replace it.
        //self::$kernel->getContainer()->set('ezpublish.siteaccess', new SiteAccess($wantedSiteAccess, 'phpunit'));

        $this->siteAccessService->setSiteAccess($this->siteAccessService->get($wantedSiteAccess));
        self::assertEquals($wantedSiteAccess, $this->siteAccessService->getCurrent()->name);

        // Shows that the SiteAccessService doesn't change the current SiteAccess from a ConfigResolver POV
        self::assertEquals('default value', $this->configResolver->getParameter('unit_test'));

        foreach($this->configResolver->getAllResolvers() as $resolver) {
            if ($resolver instanceof SiteAccessAware) {
                $resolver->setSiteAccess($this->siteAccessService->getCurrent());
            }
        }

        self::assertEquals("$wantedSiteAccess value", $this->configResolver->getParameter('unit_test'));

        /** @var Repository $repository */
        $repository = self::$kernel->getContainer()->get('ezpublish.api.repository');

        // Shows that the default connection is used
        $rootLocation = $repository->getLocationService()->loadLocation(2);
        self::assertNotNull($rootLocation);
        self::assertEquals(2, $rootLocation->id);
        self::assertEquals('Welcome to eZ Platform Enterprise Edition', $rootLocation->getContentInfo()->name);
    }
}
