<?php

namespace App\EventSubscriber;

use App\Service\CustomUrlAliasService;
use eZ\Publish\API\Repository\Events\Content\PublishVersionEvent;
use eZ\Publish\API\Repository\Events\Location\CreateLocationEvent;
use eZ\Publish\API\Repository\Events\Location\MoveSubtreeEvent;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\Core\Base\Exceptions\BadStateException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomUrlAliasEventSubscriber implements EventSubscriberInterface
{
    /** @var CustomUrlAliasService */
    private $customUrlAliasService;

    /** @var LocationService */
    private $locationService;

    public function __construct(CustomUrlAliasService $customUrlAliasService, LocationService $locationService)
    {
        $this->customUrlAliasService = $customUrlAliasService;
        $this->locationService = $locationService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PublishVersionEvent::class => 'onPublishVersion',
            CreateLocationEvent::class => 'onCreateLocation',
            MoveSubtreeEvent::class => 'onMoveSubtree',
        ];
    }

    public function onPublishVersion(PublishVersionEvent $event): void
    {
        dump($event);
        $versionInfo = $event->getVersionInfo();
        if (!$versionInfo->contentInfo->mainLocationId) {
            // Avoid contentInfo badStateException when loading its locations
            $versionInfo = $event->getContent()->versionInfo;
        }
        try {
            $locations = $this->locationService->loadLocations($versionInfo->contentInfo);
            foreach ($locations as $location) {
                $this->customUrlAliasService->addUrlAliases($location);
            }
        } catch (BadStateException $badStateException) {
            // No published version yet
        }
    }

    public function onCreateLocation(CreateLocationEvent $event): void
    {
        dump($event);
        $this->customUrlAliasService->addUrlAliases($event->getLocation());
    }

    public function onMoveSubtree(MoveSubtreeEvent $event)
    {
        dump($event);
        $location = $event->getLocation();
        //$oldParentLocation = $location->getParentLocation();
        $newParentLocation = $event->getNewParentLocation();
        $this->customUrlAliasService->addUrlAliases($location, null, $newParentLocation);
    }
}
