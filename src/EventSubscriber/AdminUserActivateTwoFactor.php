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

namespace BitExpert\SyliusTwoFactorAuthPlugin\EventSubscriber;

use BitExpert\SyliusTwoFactorAuthPlugin\Entity\TwoFactorAuthInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AdminUserActivateTwoFactor implements EventSubscriberInterface
{
    /**
     * @param UserRepositoryInterface<AdminUserInterface> $adminUserRepository
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private GoogleAuthenticatorInterface $googleAuthenticator,
        private UserRepositoryInterface $adminUserRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.admin_user.post_update' => ['activateToTwoFactor', 25],
        ];
    }

    public function activateToTwoFactor(ResourceControllerEvent $event): void
    {
        /** @var AdminUserInterface&TwoFactorAuthInterface $user */
        $user = $event->getSubject();

        // skip setup procedure when user did not activate 2FA
        if (!$user->isTwoFactorActive()) {
            return;
        }

        // set a secret before redirecting to the 2FA setup procedure
        $user->setGoogleAuthenticatorSecret($this->googleAuthenticator->generateSecret());
        $this->adminUserRepository->add($user);

        $url = $this->urlGenerator->generate('bitexpert_sylius_2fa_admin_setup_2fa');
        $event->setResponse(new RedirectResponse($url));
    }
}
