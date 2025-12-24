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

namespace BitExpert\SyliusTwoFactorAuthPlugin\Entity;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

interface TwoFactorAuthInterface extends TwoFactorInterface
{
    public function isTwoFactorActive(): bool;

    public function setTwoFactorActive(bool $twoFactorActive): void;

    public function getGoogleAuthenticatorSecret(): ?string;

    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): void;

    public function isGoogleAuthenticatorEnabled(): bool;

    public function getGoogleAuthenticatorUsername(): string;
}
