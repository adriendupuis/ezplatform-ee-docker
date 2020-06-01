<?php
// https://doc.ezplatform.com/en/3.0/tutorials/extending_admin_ui/2_creating_a_content_list/

namespace App\EventListener;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AllContentListMenuListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMenuEvent::MAIN_MENU => ['onMenuConfigure', 0],
        ];
    }

    public function onMenuConfigure(ConfigureMenuEvent $event): void
    {
        $menu = $event->getMenu();

        $menu[MainMenuBuilder::ITEM_CONTENT]->addChild(
            'all_content_list',
            [
                'label' => 'Content List',
                'route' => 'all_content_list.list',
            ]
        );
    }
}
