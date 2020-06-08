<?php
namespace App\EventListener;
use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;
use Knp\Menu\MenuItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class ConfigureMenuListener
{
    const CUSTOM__MENU__ITEM_L = 'custom__menu__item_l';

    public function onMenuConfigure(ConfigureMenuEvent $event)
    {
        /** @var MenuItem $menu */
        $menu = $event->getMenu();
        $contentMenu = $menu->getChild(MainMenuBuilder::ITEM_CONTENT);
        $contentMenu->addChild(
            self::CUSTOM__MENU__ITEM_L,
            [
                'label' => 'Customized menu item (L)',
                'route' => 'ezsystems.custompage.menu',
                'extras' => [
                    'translation_domain' => 'ezsystems_customize_admin_ui',
                ],
            ]
        );
    }
}