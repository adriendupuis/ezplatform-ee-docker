<?php

namespace App\Tests;

use eZ\Publish\Core\Repository\SiteAccessAware\Repository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SiteAccessAwareTest extends KernelTestCase
{
    public function setup(): void
    {
        self::bootKernel();
    }

    public function testSiteAccess()
    {
        var_dump(self::$kernel->getContainer()->get('ezpublish.siteaccess')); // Weird “uninitialized” siteaccess named “default” instead of named after the default siteaccess “site”

        /** @var Repository $repository */
        $repository = self::$kernel->getContainer()->get('ezpublish.api.repository');

        // Shows that the default connection is used
        $rootLocation = $repository->getLocationService()->loadLocation(2);
        self::assertNotNull($rootLocation);
        self::assertEquals(2, $rootLocation->id);
        self::assertEquals('Home', $rootLocation->getContentInfo()->name);
    }
}
