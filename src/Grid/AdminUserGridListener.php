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

namespace BitExpert\SyliusTwoFactorAuthPlugin\Grid;

use Sylius\Component\Grid\Definition\Field;
use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;

final readonly class AdminUserGridListener
{
    public function addField(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();

        $isActive2Fa = Field::fromNameAndType('twoFactorActive', 'twig');
        $isActive2Fa->setLabel('bitexpert_sylius_twofactor.admin.grid.2fa');
        $isActive2Fa->setOptions([
            'template' => '@SyliusAdmin/shared/grid/field/boolean.html.twig',
            'vars' => [
                'th_class' => 'w-1 text-center',
                'td_class' => 'text-center',
            ],
        ]);

        $grid->addField($isActive2Fa);
    }
}
