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

namespace BitExpert\SyliusTwoFactorAuthPlugin\Form\Extension;

use Sylius\Bundle\AdminBundle\Form\Type\AdminUserType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class AdminUserTwoFactorFormTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('twoFactorActive', CheckboxType::class, [
                'label' => 'bitexpert_sylius_twofactor.admin.form.2fa',
                'help' => 'bitexpert_sylius_twofactor.admin.form.2fa_help',
                'required' => false,
                'empty_data' => false,
            ])
        ;
    }

    public static function getExtendedTypes(): iterable
    {
        return [AdminUserType::class];
    }
}
