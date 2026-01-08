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

final readonly class ShopAccountMenuListener
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
        $twoFactorItem = $children[self::MENU_ID] ?? null;
        
        // First, add all items except the 2FA item
        foreach ($children as $name => $child) {
            if ($name === self::MENU_ID) {
                continue;
            }

            $newOrder[$name] = $child;
            
            // Add 2FA item after personal_information
            if ($name === 'personal_information' && $twoFactorItem !== null) {
                $newOrder[self::MENU_ID] = $twoFactorItem;
            }
        }
        
        // If 2FA wasn't added after personal_information (e.g., personal_information doesn't exist),
        // add it at the end if it exists
        if (!isset($newOrder[self::MENU_ID]) && $twoFactorItem !== null) {
            $newOrder[self::MENU_ID] = $twoFactorItem;
        }

        $menu->reorderChildren(array_keys($newOrder));
    }
}
