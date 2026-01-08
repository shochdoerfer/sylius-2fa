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

namespace Tests\BitExpert\SyliusTwoFactorAuthPlugin\Unit\Form\Renderer;

use BitExpert\SyliusTwoFactorAuthPlugin\Form\Renderer\FirewallAwareFormRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class FirewallAwareFormRendererTest extends TestCase
{
    private Environment&MockObject $twigEnvironment;

    protected function setUp(): void
    {
        $this->twigEnvironment = $this->createMock(Environment::class);
    }

    #[Test]
    public function rendersShopTemplateWhenFirewallMapIsEmpty(): void
    {
        $firewallMap = new FirewallMap(new ContainerBuilder(), []);

        $renderer = new FirewallAwareFormRenderer(
            $this->twigEnvironment,
            $firewallMap,
            'admin_template.html.twig',
            'shop_template.html.twig',
        );

        $request = Request::create('/login');
        $templateVars = ['key' => 'value'];

        $this->twigEnvironment
            ->method('render')
            ->with('shop_template.html.twig', $templateVars)
            ->willReturn('shop content');

        $response = $renderer->renderForm($request, $templateVars);

        $this->assertSame('shop content', $response->getContent());
    }

    #[Test]
    public function rendersAdminTemplateForAdminFirewall(): void
    {
        $templateVars = ['key' => 'value'];

        $this->twigEnvironment
            ->method('render')
            ->with('admin_template.html.twig', $templateVars)
            ->willReturn('admin content');

        $renderer = new FirewallAwareFormRenderer(
            $this->twigEnvironment,
            new class(new ContainerBuilder(), []) extends FirewallMap {
                public function getFirewallConfig(Request $request): FirewallConfig
                {
                    return new FirewallConfig('admin', '');
                }
            },
            'admin_template.html.twig',
            'shop_template.html.twig',
        );

        $request = Request::create('/admin/login');
        $response = $renderer->renderForm($request, $templateVars);

        $this->assertSame('admin content', $response->getContent());
    }

    #[Test]
    public function rendersShopTemplateForAdminFirewall(): void
    {
        $templateVars = ['key' => 'value'];

        $this->twigEnvironment
            ->method('render')
            ->with('shop_template.html.twig', $templateVars)
            ->willReturn('shop content');

        $renderer = new FirewallAwareFormRenderer(
            $this->twigEnvironment,
            new class(new ContainerBuilder(), []) extends FirewallMap {
                public function getFirewallConfig(Request $request): FirewallConfig
                {
                    return new FirewallConfig('shop', '');
                }
            },
            'admin_template.html.twig',
            'shop_template.html.twig',
        );

        $request = Request::create('/de/login');
        $response = $renderer->renderForm($request, $templateVars);

        $this->assertSame('shop content', $response->getContent());
    }

    #[Test]
    public function returnsEmptyResponseWhenExceptionThrown(): void
    {
        $renderer = new FirewallAwareFormRenderer(
            $this->twigEnvironment,
            new class(new ContainerBuilder(), []) extends FirewallMap {
                public function getFirewallConfig(Request $request): ?FirewallConfig
                {
                    throw new \RuntimeException('Test exception');
                }
            },
            'admin_template.html.twig',
            'shop_template.html.twig',
        );

        $request = Request::create('/admin/login');
        $response = $renderer->renderForm($request, []);

        $this->assertSame('', $response->getContent());
    }
}
