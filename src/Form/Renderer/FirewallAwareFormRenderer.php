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

namespace BitExpert\SyliusTwoFactorAuthPlugin\Form\Renderer;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\FirewallMapInterface;
use Twig\Environment;

final readonly class FirewallAwareFormRenderer implements TwoFactorFormRendererInterface
{
    public function __construct(
        private Environment $twigEnvironment,
        private FirewallMapInterface $firewallMap,
        private string $adminTemplate,
        private string $shopTemplate,
    ) {
    }

    public function renderForm(Request $request, array $templateVars): Response
    {
        $response = new Response();
        $firewallName = $this->firewallMap->getFirewallConfig($request)->getName();
        if ($firewallName === 'admin') {
            $response->setContent($this->twigEnvironment->render($this->adminTemplate, $templateVars));
        } else {
            $response->setContent($this->twigEnvironment->render($this->shopTemplate, $templateVars));
        }

        return $response;
    }
}
