<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\EventSubscriber;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddAdminMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.menu.admin.main' => 'add',
        ];
    }

    public function add(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $marketingSubmenu = $menu->getChild('marketing');
        if (!$marketingSubmenu instanceof ItemInterface) {
            return;
        }

        $marketingSubmenu
            // This will override the Sylius menu item with the same name
            ->addChild('catalog_promotions', [
                'route' => 'setono_sylius_catalog_promotion_admin_promotion_index',
            ])
            ->setAttribute('type', 'link')
            ->setLabel('setono_sylius_catalog_promotion.menu.admin.main.marketing.promotions')
            ->setLabelAttributes([
                'icon' => 'tasks',
            ])
        ;
    }
}
