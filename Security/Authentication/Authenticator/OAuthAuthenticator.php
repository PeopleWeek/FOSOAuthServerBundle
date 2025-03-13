<?php

declare(strict_types=1);

namespace FOS\OAuthServerBundle\Security\Authentication\Authenticator;

use FOS\OAuthServerBundle\Model\AccessToken;
use FOS\OAuthServerBundle\Security\Authentication\Badge\AccessTokenBadge;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use OAuth2\OAuth2;
use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class OAuthAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        protected readonly OAuth2 $serverService,
        protected readonly UserCheckerInterface $userChecker,
        protected readonly UserProviderInterface $userProvider,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $this->serverService->getBearerToken($request) !== null;
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $tokenString = $this->serverService->getBearerToken($request, true);

            if ($tokenString === null) {
                throw new AuthenticationException('OAuth2 token is missing');
            }

            $accessToken = $this->serverService->verifyAccessToken($tokenString);

            assert($accessToken instanceof AccessToken);

            $user   = $accessToken->getUser();
            // $client = $accessToken->getClient();

            $roles = $user->getRoles();
            $scope = $accessToken->getScope();

            if ($scope !== null && $scope !== "") {
                foreach (explode(" ", $scope) as $role) {
                    $roles[] = 'ROLE_' . mb_strtoupper($role);
                }
            }

            $roles = array_unique($roles, SORT_REGULAR);

            return new SelfValidatingPassport(
                new UserBadge($user->getUserIdentifier()),
                [new AccessTokenBadge($accessToken, $roles)]
            );
        } catch (OAuth2ServerException $e) {
            throw new AuthenticationException("OAuth2 failed", 0, $e);
        }
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $badge = $passport->getBadge(AccessTokenBadge::class);

        assert($badge instanceof AccessTokenBadge);

        $token = new OAuthToken($badge->getRoles());
        $token->setToken($badge->getAccessToken()->getToken());
        $token->setUser($badge->getAccessToken()->getUser());
        $token->setAuthenticated(true);

        return $token;
    }

    public function createAuthenticatedToken(PassportInterface $passport, string $firewallName): TokenInterface
    {
        $badge = $passport->getBadge(AccessTokenBadge::class);

        assert($badge instanceof AccessTokenBadge);

        $token = new OAuthToken($badge->getRoles());
        $token->setToken($badge->getAccessToken()->getToken());
        $token->setUser($badge->getAccessToken()->getUser());
        $token->setAuthenticated(true);

        return $token;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Continue request
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            [
                'error' => 'access_denied',
                'error_description' => $exception->getMessage(),
            ],
            Response::HTTP_UNAUTHORIZED,
            [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache'
            ]
        );
    }
}