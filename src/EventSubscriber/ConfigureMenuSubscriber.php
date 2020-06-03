<?php

namespace App\EventSubscriber;

use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;
use Knp\Menu\MenuItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;

class ConfigureMenuSubscriber implements EventSubscriberInterface
{
    const CUSTOM__MENU__ITEM_S = 'custom__menu__item_s';

    public function onEzplatformAdminUiMenuConfigureMainMenu(ConfigureMenuEvent $event)
    {
        /** @var MenuItem $menu */
        $menu = $event->getMenu();
        $contentMenu = $menu->getChild(MainMenuBuilder::ITEM_CONTENT);
        $contentMenu->addChild(
            self::CUSTOM__MENU__ITEM_S,
            [
                'label' => 'Customized menu item (S)',
                'route' => 'ezsystems.custompage.menu',
                'extras' => [
                    'translation_domain' => 'ezsystems_customize_admin_ui',
                ],
            ]
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'ezplatform_admin_ui.menu_configure.main_menu' => 'onEzplatformAdminUiMenuConfigureMainMenu',
        ];
    }
}
