<?php

declare(strict_types=1);

namespace Tests\BitExpert\SyliusTwoFactorAuthPlugin\Entity\User;

use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Sylius\Component\Core\Model\AdminUser as BaseAdminUser;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_admin_user')]
class AdminUser extends BaseAdminUser implements TwoFactorInterface
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
        return $this->username;
    }
}
