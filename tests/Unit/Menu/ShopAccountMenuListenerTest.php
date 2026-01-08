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

namespace Tests\BitExpert\SyliusTwoFactorAuthPlugin\Unit\Menu;

use BitExpert\SyliusTwoFactorAuthPlugin\Menu\ShopAccountMenuListener;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class ShopAccountMenuListenerTest extends TestCase
{
    #[Test]
    public function addsTwoFactorMenuItemToShopAccountMenu(): void
    {
        $menuItem = $this->createMock(ItemInterface::class);
        $menuItem->expects($this->once())->method('addChild');
        $menuItem->expects($this->once())->method('getChildren')->willReturn([]);
        $menuItem->expects($this->once())->method('reorderChildren');

        $event = $this->createMock(MenuBuilderEvent::class);
        $event->method('getMenu')->willReturn($menuItem);

        $listener = new ShopAccountMenuListener();
        $listener->addMenuItem($event);
    }
}
