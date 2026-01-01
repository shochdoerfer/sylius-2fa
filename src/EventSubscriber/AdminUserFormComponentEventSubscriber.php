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

use Sylius\Bundle\UiBundle\Twig\Component\ResourceFormComponent;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

final readonly class AdminUserFormComponentEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [PreRenderEvent::class => 'onPreRender'];
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        $component = $event->getComponent();
        if (!$component instanceof ResourceFormComponent) {
            return;
        }

        if (!$component->resource instanceof AdminUserInterface) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        if (!($user instanceof AdminUserInterface)) {
            return;
        }

        $variables = $event->getVariables();
        $variables['can_configure_2fa'] = $user->getId() === $component->resource->getId();
        $event->setVariables($variables);
    }
}
