<?php

/*
 * This file is part of the Sylius 2FA Auth package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace BitExpert\SyliusTwoFactorAuthPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

class ShopAccountMenuListener
{
    private const MENU_ID = 'two_factor_setup';

    public function addMenuItem(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        // add new item to the menu
        $menu
            ->addChild(self::MENU_ID, [
                'route' => 'bitexpert_sylius_2fa_shop_account_2fa_overview',
            ])
            ->setLabel('bitexpert_sylius_twofactor.shop.menu.label')
            ->setLabelAttribute('icon', 'tabler:shield-lock');

        // re-arrange menu items to put the new item after personal information
        $newOrder = [];
        $children = $menu->getChildren();
        foreach ($children as $name => $child) {
            if ($name === self::MENU_ID) {
                continue;
            }

            $newOrder[$name] = $child;
            if ($name === 'personal_information') {
                $newOrder[self::MENU_ID] = $children[self::MENU_ID];
            }
        }

        $menu->reorderChildren(array_keys($newOrder));
    }
}
