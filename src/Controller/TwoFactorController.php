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

namespace BitExpert\SyliusTwoFactorAuthPlugin\Controller;

use BitExpert\SyliusTwoFactorAuthPlugin\Entity\TwoFactorAuthInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Exception\ValidationException;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Sylius\Component\User\Model\UserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Sylius\Resource\Metadata\Metadata;
use Sylius\TwigHooks\Bag\DataBag;
use Sylius\TwigHooks\Bag\ScalarDataBag;
use Sylius\TwigHooks\Hook\Metadata\HookMetadata;
use Sylius\TwigHooks\Hookable\HookableTemplate;
use Sylius\TwigHooks\Hookable\Metadata\HookableMetadataFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Flow\FormFlowInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class TwoFactorController extends AbstractController
{
    private const AUTH_SECRET_SESSION_KEY = 'googleAuthSecret';

    private const AUTH_EMAIL_SESSION_KEY = 'emailAuthCode';

    /**
     * @param UserRepositoryInterface<UserInterface> $repository
     * @param class-string $googleFormFlow
     * @param class-string $emailFormFlow
     */
    public function __construct(
        private readonly HookableMetadataFactoryInterface $hookableMetadataFactory,
        private readonly GoogleAuthenticatorInterface $googleAuthenticator,
        private readonly CodeGeneratorInterface $codeGenerator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserRepositoryInterface $repository,
        private readonly string $googleFormFlow,
        private readonly string $emailFormFlow,
        private readonly string $entity,
        private readonly string $redirectRoute,
        private readonly string $template,
    ) {
    }

    public function setupGoogleAuth(Request $request): Response
    {
        $type = 'google';

        /** @var FormInterface&FormFlowInterface $flow */
        $flow = $this->createForm($this->googleFormFlow, []);
        return $this->setupTwoFactorFlow($flow, $request, $type);
    }

    public function setupEmailAuth(Request $request): Response
    {
        $type = 'email';

        /** @var (UserInterface&TwoFactorAuthInterface)|null $resource */
        $resource = $this->tokenStorage->getToken()?->getUser();
        if (!$resource instanceof TwoFactorAuthInterface) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('GET')) {
            // the user entered the verification_code step, send an email with the auth code and persist in the session
            $this->codeGenerator->generateAndSend($resource);
            $request->getSession()->set(self::AUTH_EMAIL_SESSION_KEY, $resource->getEmailAuthCode());
        }

        /** @var FormInterface&FormFlowInterface $flow */
        $flow = $this->createForm($this->emailFormFlow, []);
        return $this->setupTwoFactorFlow($flow, $request, $type);
    }

    /**
     * @param FormInterface&FormFlowInterface $flow
     */
    protected function setupTwoFactorFlow(FormInterface $flow, Request $request, string $type): Response
    {
        /** @var (UserInterface&TwoFactorAuthInterface)|null $resource */
        $resource = $this->tokenStorage->getToken()?->getUser();
        if (!$resource instanceof TwoFactorAuthInterface) {
            throw $this->createAccessDeniedException();
        }

        $flow->handleRequest($request);
        if ($flow->isSubmitted() && $flow->isValid() && $flow->isFinished()) {
            /** @var array<string, array<string, string>> $data */
            $data = $flow->getData();
            $code = $data['verify_code']['verification_code'] ?? '';

            /** @var string|null $secret */
            $secret = $request->getSession()->get(self::AUTH_SECRET_SESSION_KEY);
            if ($secret === null) {
                throw $this->createAccessDeniedException();
            }

            if ($type === 'google') {
                /** @phpstan-ignore-next-line */
                $resource->setGoogleAuthenticatorSecret($secret);
                if ($this->googleAuthenticator->checkCode($resource, $code)) {
                    $this->repository->add($resource);
                    $this->addFlash('success', 'bitexpert_sylius_twofactor.2fa_setup.success');
                    return $this->redirectToRoute($this->redirectRoute);
                }
            } else if ($type === 'email') {
                $resource->setEmailAuthCode($secret);
                if ($code === $resource->getEmailAuthCode()) {
                    $this->repository->add($resource);
                    $this->addFlash('success', 'bitexpert_sylius_twofactor.2fa_setup.success');
                    return $this->redirectToRoute($this->redirectRoute);
                }
            }

            $this->addFlash('error', 'bitexpert_sylius_twofactor.2fa_setup.failed');
            return $this->redirectToRoute($this->redirectRoute);
        }

        $metadata = Metadata::fromAliasAndConfiguration(sprintf('sylius.%s', $this->entity), ['driver' => 'doctrine/orm']);

        $hookMetadata = new HookMetadata($this->entity, new DataBag(['applicationName' => 'sylius']));
        $hookable = new HookableTemplate($this->entity, 'show', '', ['applicationName' => 'sylius'], ['resource_name' => $this->entity]);
        $hookableMetadata = $this->hookableMetadataFactory->create(
            $hookMetadata,
            new DataBag(['resource' => $resource]),
            /** @phpstan-ignore-next-line */
            new ScalarDataBag($hookable->configuration),
            [],
        );

        return $this->render($this->template, [
            'configuration' => $hookableMetadata->configuration,
            'metadata' => $metadata,
            'resource' => $resource,
            'form' => $flow->getStepForm(),
            'type' => $type,
        ]);
    }

    public function displayGoogleAuthenticatorQrCode(Request $request): Response
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof UserInterface || !$user instanceof TwoFactorAuthInterface) {
            throw $this->createNotFoundException();
        }

        // generate secret to display the QR code for. Persist it in the session to be able to verify the code later
        $secret = $this->googleAuthenticator->generateSecret();
        /** @phpstan-ignore-next-line */
        $user->setGoogleAuthenticatorSecret($secret);
        $request->getSession()->set(self::AUTH_SECRET_SESSION_KEY, $secret);

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

        $result = '';
        try {
            $result = $builder->build()->getString();
        } catch (ValidationException $e) {
        }

        return new Response($result, 200, ['Content-Type' => 'image/png']);
    }
}
