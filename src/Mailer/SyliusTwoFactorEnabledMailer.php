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

namespace BitExpert\SyliusTwoFactorAuthPlugin\Mailer;

use Psr\Log\LoggerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class SyliusTwoFactorEnabledMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private ChannelContextInterface $channelContext,
        private LocaleContextInterface $localeContext,
        private LoggerInterface $logger,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public function send2FaEnabledMail(string $recipientEmail): void
    {
        $channel = $this->channelContext->getChannel();

        if (!$channel instanceof ChannelInterface) {
            $this->logger->info('Cannot send 2FA activation email: channel is not a Sylius ChannelInterface instance.');

            return;
        }

        $contactEmail = $channel->getContactEmail();
        if ($contactEmail === null || $contactEmail === '') {
            $this->logger->info(
                'Cannot send 2FA activation email: no contact email configured for channel "{channel}".',
                ['channel' => $channel->getCode()],
            );

            return;
        }

        $localeCode = $this->localeContext->getLocaleCode();

        try {
            $body = $this->twig->render('@BitExpertSyliusTwoFactorAuthPlugin/email/2fa_enabled.html.twig', [
                'channel' => $channel,
                'localeCode' => $localeCode,
            ]);

            $email = (new Email())
                ->from($contactEmail)
                ->to($recipientEmail)
                ->subject($this->translator->trans('bitexpert_sylius_twofactor.2fa_enabled_email.subject', locale: $localeCode))
                ->html($body);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to send 2FA activation email to "{recipient}": {error}',
                ['recipient' => $recipientEmail, 'error' => $e->getMessage()],
            );
        }
    }
}
