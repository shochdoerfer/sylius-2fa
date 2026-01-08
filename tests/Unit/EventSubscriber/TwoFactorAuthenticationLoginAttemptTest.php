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

namespace Tests\BitExpert\SyliusTwoFactorAuthPlugin\Unit\EventSubscriber;

use BitExpert\SyliusTwoFactorAuthPlugin\EventSubscriber\TwoFactorAuthenticationLoginAttempt;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\HttpFoundation\Request;

class TwoFactorAuthenticationLoginAttemptTest extends TestCase
{
    private TwoFactorAuthenticationLoginAttempt $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new TwoFactorAuthenticationLoginAttempt();
    }

    #[Test]
    public function checkSubscribedTwoFactorAuthenticationAttemptEvent(): void
    {
        $events = $this->subscriber::getSubscribedEvents();

        self::assertArrayHasKey('scheb_two_factor.authentication.attempt', $events);
        self::assertCount(1, $events);
        self::assertSame(['onLoginAttempt', 100], $events['scheb_two_factor.authentication.attempt']);
    }

    #[Test]
    public function doesNothingWhenAuthCodeParamExists(): void
    {
        $request = new Request();
        $request->request->set('_auth_code', '123456');

        $event = $this->createMock(TwoFactorAuthenticationEvent::class);
        $event->method('getRequest')->willReturn($request);

        $this->subscriber->onLoginAttempt($event);

        self::assertSame('123456', $request->request->get('_auth_code'));
    }

    #[Test]
    public function combinesAuthCodePartsIntoSingleParam(): void
    {
        $request = new Request();
        $request->request->set('_auth_code_0', '1');
        $request->request->set('_auth_code_1', '2');
        $request->request->set('_auth_code_2', '3');
        $request->request->set('_auth_code_3', '4');
        $request->request->set('_auth_code_4', '5');
        $request->request->set('_auth_code_5', '6');

        $event = $this->createMock(TwoFactorAuthenticationEvent::class);
        $event->method('getRequest')->willReturn($request);

        $this->subscriber->onLoginAttempt($event);

        self::assertSame('123456', $request->request->get('_auth_code'));
    }

    #[Test]
    public function doesNotCreateAuthCodeWhenPartsAreMissing(): void
    {
        $request = new Request();
        $request->request->set('_auth_code_0', '1');
        $request->request->set('_auth_code_1', '2');

        $event = $this->createMock(TwoFactorAuthenticationEvent::class);
        $event->method('getRequest')->willReturn($request);

        $this->subscriber->onLoginAttempt($event);

        self::assertNull($request->request->get('_auth_code'));
    }

    #[Test]
    public function onlyCreateAuthCodeWhenAllPartsArePresent(): void
    {
        $request = new Request();
        $request->request->set('_auth_code_0', '1');
        $request->request->set('_auth_code_1', '2');
        $request->request->set('_auth_code_2', '3');
        $request->request->set('_auth_code_3', '4');
        $request->request->set('_auth_code_4', '5');

        $event = $this->createMock(TwoFactorAuthenticationEvent::class);
        $event->method('getRequest')->willReturn($request);

        $this->subscriber->onLoginAttempt($event);

        self::assertNull($request->request->get('_auth_code'));
    }

    #[Test]
    public function skipsEmptyPartsInAuthCodeParts(): void
    {
        $request = new Request();
        $request->request->set('_auth_code_0', '1');
        $request->request->set('_auth_code_1', '2');
        $request->request->set('_auth_code_2', '');
        $request->request->set('_auth_code_3', '4');
        $request->request->set('_auth_code_4', '5');
        $request->request->set('_auth_code_5', '6');

        $event = $this->createMock(TwoFactorAuthenticationEvent::class);
        $event->method('getRequest')->willReturn($request);

        $this->subscriber->onLoginAttempt($event);

        self::assertSame('12456', $request->request->get('_auth_code'));
    }

    #[Test]
    public function handlesNonSequentialPartIndices(): void
    {
        $request = new Request();
        $request->request->set('_auth_code_0', '1');
        $request->request->set('_auth_code_2', '3');
        $request->request->set('_auth_code_3', '');
        $request->request->set('_auth_code_4', '5');
        $request->request->set('_auth_code_6', '7');

        $event = $this->createMock(TwoFactorAuthenticationEvent::class);
        $event->method('getRequest')->willReturn($request);

        $this->subscriber->onLoginAttempt($event);

        self::assertNull($request->request->get('_auth_code'));
    }
}
