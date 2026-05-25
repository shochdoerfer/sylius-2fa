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

use BitExpert\SyliusTwoFactorAuthPlugin\Mailer\SyliusTwoFactorEnabledMailer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class SyliusTwoFactorEnabledMailerTest extends TestCase
{
    /** @var MockObject&MailerInterface */
    private MailerInterface $mailer;

    /** @var MockObject&ChannelContextInterface */
    private ChannelContextInterface $channelContext;

    /** @var MockObject&LocaleContextInterface */
    private LocaleContextInterface $localeContext;

    /** @var MockObject&LoggerInterface */
    private LoggerInterface $logger;

    /** @var MockObject&Environment */
    private Environment $twig;

    /** @var MockObject&TranslatorInterface */
    private TranslatorInterface $translator;

    private SyliusTwoFactorEnabledMailer $syliusTwoFactorEnabledMailer;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->localeContext = $this->createMock(LocaleContextInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->syliusTwoFactorEnabledMailer = new SyliusTwoFactorEnabledMailer(
            $this->mailer,
            $this->channelContext,
            $this->localeContext,
            $this->logger,
            $this->twig,
            $this->translator,
        );
    }

    #[Test]
    public function itLogsAndSkipsWhenChannelIsNotASyliusChannel(): void
    {
        $channel = $this->createMock(BaseChannelInterface::class);
        $this->channelContext->method('getChannel')->willReturn($channel);

        $this->logger->expects($this->once())->method('info');
        $this->mailer->expects($this->never())->method('send');

        $this->syliusTwoFactorEnabledMailer->send2FaEnabledMail('customer@example.com');
    }

    #[Test]
    public function itLogsAndSkipsWhenContactEmailIsNull(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getContactEmail')->willReturn(null);
        $channel->method('getCode')->willReturn('FASHION_WEB');
        $this->channelContext->method('getChannel')->willReturn($channel);

        $this->logger->expects($this->once())->method('info');
        $this->mailer->expects($this->never())->method('send');

        $this->syliusTwoFactorEnabledMailer->send2FaEnabledMail('customer@example.com');
    }

    #[Test]
    public function itLogsAndSkipsWhenContactEmailIsEmpty(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getContactEmail')->willReturn('');
        $channel->method('getCode')->willReturn('FASHION_WEB');
        $this->channelContext->method('getChannel')->willReturn($channel);

        $this->logger->expects($this->once())->method('info');
        $this->mailer->expects($this->never())->method('send');

        $this->syliusTwoFactorEnabledMailer->send2FaEnabledMail('customer@example.com');
    }

    #[Test]
    public function itSendsEmailWithContactEmailAsFromAndRecipientAsTo(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getContactEmail')->willReturn('shop@example.com');
        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->localeContext->method('getLocaleCode')->willReturn('en_US');
        $this->twig->method('render')->willReturn('2FA activated');
        $this->translator->method('trans')->willReturn('Two-Factor Authentication Activated');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $from = $email->getFrom();
                $this->assertCount(1, $from);
                $this->assertSame('shop@example.com', $from[0]->getAddress());

                $to = $email->getTo();
                $this->assertCount(1, $to);
                $this->assertSame('customer@example.com', $to[0]->getAddress());

                $this->assertSame('Two-Factor Authentication Activated', $email->getSubject());

                return true;
            }));

        $this->syliusTwoFactorEnabledMailer->send2FaEnabledMail('customer@example.com');
    }

    #[Test]
    public function itRendersTemplateWithChannelAndLocale(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getContactEmail')->willReturn('shop@example.com');
        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->localeContext->method('getLocaleCode')->willReturn('de_DE');
        $this->translator->method('trans')->willReturn('Zwei-Faktor-Authentifizierung aktiviert');
        $this->mailer->method('send');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@BitExpertSyliusTwoFactorAuthPlugin/email/2fa_enabled.html.twig',
                [
                    'channel' => $channel,
                    'localeCode' => 'de_DE',
                ],
            )
            ->willReturn('2FA aktiviert');

        $this->syliusTwoFactorEnabledMailer->send2FaEnabledMail('customer@example.com');
    }

    #[Test]
    public function itPassesLocaleToTranslator(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getContactEmail')->willReturn('shop@example.com');
        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->localeContext->method('getLocaleCode')->willReturn('de_DE');
        $this->twig->method('render')->willReturn('2FA aktiviert');
        $this->mailer->method('send');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('bitexpert_sylius_twofactor.2fa_enabled_email.subject', [], null, 'de_DE')
            ->willReturn('Zwei-Faktor-Authentifizierung aktiviert');

        $this->syliusTwoFactorEnabledMailer->send2FaEnabledMail('customer@example.com');
    }
}
