<?php

namespace App\Tests;

use eZ\Publish\Core\MVC\Symfony\SiteAccess\SiteAccessService;
use eZ\Publish\Core\Repository\SiteAccessAware\Repository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SiteAccessAwareTest extends KernelTestCase
{
    /** @var SiteAccessService */
    private $siteAccessService;

    public function setup(): void
    {
        self::bootKernel();
        $this->siteAccessService = self::$kernel->getContainer()->get('test_alias.ezpublish.siteaccess_service');
    }

    public function testSiteAccess()
    {
        $wantedSiteAccess = 'site';
        //$wantedSiteAccess = 'admin';

        $this->siteAccessService->setSiteAccess($this->siteAccessService->get($wantedSiteAccess));

        self::assertEquals($wantedSiteAccess, $this->siteAccessService->getCurrent()->name);

        /** @var Repository $repository */
        $repository = self::$kernel->getContainer()->get('ezpublish.api.repository');
        $rootLocation = $repository->getLocationService()->loadLocation(2);
        self::assertEquals(2, $rootLocation->id);
        self::assertEquals('Welcome to eZ Platform Enterprise Edition', $rootLocation->getContentInfo()->name);
    }

}