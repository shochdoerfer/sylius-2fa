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

use Doctrine\ORM\Mapping as ORM;

trait TwoFactorAuthTrait
{
    #[ORM\Column(name: '2fa_active', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $twoFactorActive = false;

    #[ORM\Column(name: 'google_authenticator_secret', type: 'string', nullable: true)]
    private ?string $googleAuthenticatorSecret;

    public function isTwoFactorActive(): bool
    {
        return $this->twoFactorActive;
    }

    public function setTwoFactorActive(bool $twoFactorActive): void
    {
        $this->twoFactorActive = $twoFactorActive;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): void
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return null !== $this->googleAuthenticatorSecret;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        if ($this->username === null) {
            return '';
        }

        return $this->username;
    }
}
