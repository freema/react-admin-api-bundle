<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Security;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Vlp\Mailer\Entity\Admin;

class TokenAuthenticator extends AbstractAuthenticator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ReglogUserService $reglogUserService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->logger = new NullLogger();
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/admin/api/v1');
    }

    private function extractToken(Request $request): ?string
    {
        $token = $request->headers->get('x-authorization');
        if (!$token) {
            $token = $request->headers->get('authorization');
        }

        if (!$token) {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $token, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->extractToken($request);

        if (!$token) {
            throw new AuthenticationException('No valid authorization token provided');
        }

        return new SelfValidatingPassport(
            new UserBadge($token, function ($token) {
                try {
                    $resourceOwner = $this->reglogUserService->getResourceOwner($token);

                    $email = $resourceOwner->getEmail();
                    $admin = $this->entityManager->getRepository(Admin::class)
                        ->findOneBy(['login' => $email]);

                    if (!$admin) {
                        throw new AuthenticationException('Unauthorized user');
                    }

                    $admin->setResourceOwner($resourceOwner);
                    $admin->setAccessToken($token);
                    $admin->setLastLogin(new \DateTimeImmutable());

                    $this->entityManager->flush();

                    return $admin;
                } catch (\Exception $e) {
                    $this->logger?->notice('Authentication failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw new AuthenticationException('Invalid token or unauthorized user');
                }
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => $exception->getMessage(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
