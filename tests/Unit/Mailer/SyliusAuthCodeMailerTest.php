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

namespace Tests\BitExpert\SyliusTwoFactorAuthPlugin\Unit\Mailer;

use BitExpert\SyliusTwoFactorAuthPlugin\Entity\TwoFactorAuthInterface;
use BitExpert\SyliusTwoFactorAuthPlugin\Mailer\SyliusAuthCodeMailer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Sylius\Component\User\Model\UserInterface;

final class SyliusAuthCodeMailerTest extends TestCase
{
    /**
     * @var MockObject&SenderInterface
     */
    private SenderInterface $sender;
    /**
     * @var MockObject&ChannelContextInterface
     */
    private ChannelContextInterface $channelContext;
    /**
     * @var MockObject&LocaleContextInterface
     */
    private LocaleContextInterface $localeContext;
    private SyliusAuthCodeMailer $mailer;

    protected function setUp(): void
    {
        $this->sender = $this->createMock(SenderInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->localeContext = $this->createMock(LocaleContextInterface::class);

        $this->mailer = new SyliusAuthCodeMailer(
            $this->sender,
            $this->channelContext,
            $this->localeContext
        );
    }

    #[Test]
    public function itSendsAnAuthCodeEmail(): void
    {
        /** @var MockObject&TwoFactorInterface&UserInterface&TwoFactorAuthInterface $user */
        $user = $this->createMockForIntersectionOfInterfaces([UserInterface::class, TwoFactorAuthInterface::class]);
        $channel = $this->createMock(ChannelInterface::class);

        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getEmailAuthCode')->willReturn('123456');

        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->localeContext->method('getLocaleCode')->willReturn('en_US');

        $this->sender->expects($this->once())
            ->method('send')
            ->with(
                'bitexpert_sylius_2fa_auth_code',
                ['test@example.com'],
                [
                    'authCode' => '123456',
                    'channel' => $channel,
                    'localeCode' => 'en_US',
                ]
            );

        $this->mailer->sendAuthCode($user);
    }
}
