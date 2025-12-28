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

namespace BitExpert\SyliusTwoFactorAuthPlugin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class VerificationCodeTransformer implements DataTransformerInterface
{
    public function transform($value): array
    {
        if (!$value || !is_string($value)) {
            return array_fill(0, 6, '');
        }

        return str_split($value);
    }

    public function reverseTransform($value): string
    {
        if (!is_array($value)) {
            return '';
        }

        return implode('', $value);
    }
}
