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

namespace BitExpert\SyliusTwoFactorAuthPlugin\Controller\Admin;

use BitExpert\SyliusTwoFactorAuthPlugin\Entity\TwoFactorAuthInterface;
use BitExpert\SyliusTwoFactorAuthPlugin\Form\Type\Email\EmailSetupFormFlow;
use BitExpert\SyliusTwoFactorAuthPlugin\Form\Type\Google\GoogleSetupFormFlowType;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Sylius\Resource\Metadata\Metadata;
use Sylius\TwigHooks\Bag\DataBag;
use Sylius\TwigHooks\Bag\ScalarDataBag;
use Sylius\TwigHooks\Hook\Metadata\HookMetadata;
use Sylius\TwigHooks\Hookable\HookableTemplate;
use Sylius\TwigHooks\Hookable\Metadata\HookableMetadataFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class TwoFactorController extends AbstractController
{
    private const AUTH_SECRET_SESSION_KEY = 'googleAuthSecret';

    private const AUTH_EMAIL_SESSION_KEY = 'emailAuthCode';

    public function __construct(
        private readonly HookableMetadataFactoryInterface $hookableMetadataFactory,
        private readonly UserRepositoryInterface $adminUserRepository,
        private readonly GoogleAuthenticatorInterface $googleAuthenticator,
        private readonly CodeGeneratorInterface $codeGenerator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function setupGoogleAuth(Request $request): Response
    {
        $metadata = Metadata::fromAliasAndConfiguration('sylius.admin_user', ['driver' => 'doctrine/orm']);

        /** @var (AdminUser&TwoFactorAuthInterface)|null $resource */
        $resource = $this->tokenStorage->getToken()?->getUser();
        if (!$resource instanceof TwoFactorAuthInterface) {
            throw $this->createAccessDeniedException();
        }

        $hookMetadata = new HookMetadata('admin_user', new DataBag(['applicationName' => 'sylius']));
        $hookable = new HookableTemplate('admin_user', 'show', '', ['applicationName' => 'sylius'], ['resource_name' => 'admin_user']);
        $hookableMetadata = $this->hookableMetadataFactory->create(
            $hookMetadata,
            new DataBag(['resource' => $resource]),
            /** @phpstan-ignore-next-line */
            new ScalarDataBag($hookable->configuration),
            [],
        );

        $flow = $this->createForm(GoogleSetupFormFlowType::class, [])
            ->handleRequest($request);

        if ($flow->isSubmitted() && $flow->isValid() && $flow->isFinished()) {
            /** @var array<string, array<string, string>> $data */
            $data = $flow->getData();
            $code = $data['verify_code']['verification_code'] ?? '';

            $authenticatorSecret = $request->getSession()->get(self::AUTH_SECRET_SESSION_KEY);
            if ($authenticatorSecret === null) {
                $this->createAccessDeniedException();
            }

            $resource->setGoogleAuthenticatorSecret($authenticatorSecret);
            if ($this->googleAuthenticator->checkCode($resource, $code)) {
                // check ok, persist the secret in the user object
                $this->adminUserRepository->add($resource);

                $this->addFlash('success', 'bitexpert_sylius_twofactor.2fa_setup.success');

                return $this->redirectToRoute('sylius_admin_dashboard');
            }

            $this->addFlash('error', 'bitexpert_sylius_twofactor.2fa_setup.failed');

            return $this->redirectToRoute('sylius_admin_dashboard');
        }

        return $this->render('@BitExpertSyliusTwoFactorAuthPlugin/admin/two_factor_setup.html.twig', [
            'configuration' => $hookableMetadata->configuration,
            'metadata' => $metadata,
            'resource' => $resource,
            'form' => $flow->getStepForm(),
            'type' => 'google',
        ]);
    }

    public function displayGoogleAuthenticatorQrCode(Request $request): Response
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof TwoFactorAuthInterface) {
            throw $this->createNotFoundException('Cannot display QR code');
        }

        // generate secret to display the QR code for. Persist it in the session to be able to verify the code later
        $user->setGoogleAuthenticatorSecret($this->googleAuthenticator->generateSecret());
        $request->getSession()->set(self::AUTH_SECRET_SESSION_KEY, $user->getGoogleAuthenticatorSecret());

        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            data: $this->googleAuthenticator->getQRContent($user),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $result = $builder->build();

        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }

    public function setupEmailAuth(Request $request): Response
    {
        $metadata = Metadata::fromAliasAndConfiguration('sylius.admin_user', ['driver' => 'doctrine/orm']);

        /** @var (AdminUser&TwoFactorAuthInterface)|null $resource */
        $resource = $this->tokenStorage->getToken()?->getUser();
        if (!$resource instanceof TwoFactorAuthInterface) {
            $this->createAccessDeniedException();
        }

        if ($request->isMethod('GET')) {
            // the user entered the verification_code step, send an email with the auth code and persist in the session
            $this->codeGenerator->generateAndSend($resource);
            $request->getSession()->set(self::AUTH_EMAIL_SESSION_KEY, $resource->getEmailAuthCode());
        }

        $hookMetadata = new HookMetadata('admin_user', new DataBag(['applicationName' => 'sylius']));
        $hookable = new HookableTemplate('admin_user', 'show', '', ['applicationName' => 'sylius'], ['resource_name' => 'admin_user']);
        $hookableMetadata = $this->hookableMetadataFactory->create(
            $hookMetadata,
            new DataBag(['resource' => $resource]),
            /** @phpstan-ignore-next-line */
            new ScalarDataBag($hookable->configuration),
            [],
        );

        $flow = $this->createForm(EmailSetupFormFlow::class, [])
            ->handleRequest($request);

        if ($flow->isSubmitted() && $flow->isValid() && $flow->isFinished()) {
            /** @var array<string, array<string, string>> $data */
            $data = $flow->getData();
            $code = $data['verify_code']['verification_code'] ?? '';

            $authCode = $request->getSession()->get(self::AUTH_EMAIL_SESSION_KEY);
            if ($authCode === null) {
                $this->createAccessDeniedException();
            }

            $resource->setEmailAuthCode($authCode);
            if ($code === $resource->getEmailAuthCode()) {
                // check ok, persist the secret in the user object
                $this->adminUserRepository->add($resource);

                $this->addFlash('success', 'bitexpert_sylius_twofactor.2fa_setup.success');

                return $this->redirectToRoute('sylius_admin_dashboard');
            }

            $this->addFlash('error', 'bitexpert_sylius_twofactor.2fa_setup.failed');

            return $this->redirectToRoute('sylius_admin_dashboard');
        }

        return $this->render('@BitExpertSyliusTwoFactorAuthPlugin/admin/two_factor_setup.html.twig', [
            'configuration' => $hookableMetadata->configuration,
            'metadata' => $metadata,
            'resource' => $resource,
            'form' => $flow->getStepForm(),
            'type' => 'email',
        ]);
    }
}
