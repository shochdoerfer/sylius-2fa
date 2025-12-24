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

namespace BitExpert\SyliusTwoFactorAuthPlugin\Form\Common;

use BitExpert\SyliusTwoFactorAuthPlugin\Form\DataTransformer\DigitsToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class CodeType extends AbstractType
{
    public function __construct(
        private readonly DigitsToStringTransformer $transformer
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        for ($i = 0; $i < 6; $i++) {
            $builder->add((string) $i, TextType::class, [
                'attr' => [
                    'maxlength' => 1,
                    'class' => 'digit-input',
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]',
                ],
            ]);
        }

        $builder->addModelTransformer($this->transformer);
    }
}
