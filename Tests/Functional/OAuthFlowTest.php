<?php

declare(strict_types=1);

namespace FOS\OAuthServerBundle\Tests\Functional;

use FOS\OAuthServerBundle\Model\AccessToken;
use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;
use FOS\OAuthServerBundle\Model\AuthCodeManagerInterface;
use FOS\OAuthServerBundle\Model\Client;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use FOS\OAuthServerBundle\Model\RefreshToken;
use FOS\OAuthServerBundle\Model\RefreshTokenManagerInterface;
use FOS\OAuthServerBundle\Model\TokenInterface;
use OAuth2\OAuth2;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuthFlowTest extends TestCase
{
    public function testPasswordGrantRefreshAndBearerAuthentication(): void
    {
        $kernel = static::bootKernel(['env' => 'custom']);
        $container = $kernel->getContainer();
        $client = new Client();
        $client->setAllowedGrantTypes([OAuth2::GRANT_TYPE_USER_CREDENTIALS, OAuth2::GRANT_TYPE_REFRESH_TOKEN]);
        $clients = $this->createMock(ClientManagerInterface::class);
        $clients->method('findClientByPublicId')->willReturnCallback(
            static fn ($id) => $id === $client->getPublicId() ? $client : null
        );
        $container->set('test.client_manager', $clients);
        $container->set('test.auth_code_manager', $this->createMock(AuthCodeManagerInterface::class));

        $accessTokens = [];
        $refreshTokens = [];
        $this->registerTokenManager($container, 'test.access_token_manager', AccessTokenManagerInterface::class, AccessToken::class, $accessTokens);
        $this->registerTokenManager($container, 'test.refresh_token_manager', RefreshTokenManagerInterface::class, RefreshToken::class, $refreshTokens);

        $credentials = ['client_id' => $client->getPublicId(), 'client_secret' => $client->getSecret()];
        $response = $kernel->handle(Request::create('/oauth/v2/token', 'POST', $credentials + [
            'grant_type' => 'password', 'username' => 'alice', 'password' => 'secret', 'scope' => 'api',
        ]));
        self::assertSame(200, $response->getStatusCode(), $response->getContent());
        $issued = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('access_token', $issued);
        self::assertArrayHasKey('refresh_token', $issued);

        $request = static fn (string $token) => Request::create('/private', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertSame(200, $kernel->handle($request($issued['access_token']))->getStatusCode());
        self::assertSame(401, $kernel->handle($request('unknown-token'))->getStatusCode());

        $accessTokens[$issued['access_token']]->setScope('');
        self::assertSame(403, $kernel->handle($request($issued['access_token']))->getStatusCode());
        $accessTokens[$issued['access_token']]->setScope('api');
        $accessTokens[$issued['access_token']]->setExpiresAt(time() - 60);
        self::assertSame(401, $kernel->handle($request($issued['access_token']))->getStatusCode());

        $response = $kernel->handle(Request::create('/oauth/v2/token', 'POST', $credentials + [
            'grant_type' => 'refresh_token', 'refresh_token' => $issued['refresh_token'],
        ]));
        self::assertSame(200, $response->getStatusCode(), $response->getContent());
        $refreshed = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotSame($issued['access_token'], $refreshed['access_token']);
        self::assertSame(200, $kernel->handle($request($refreshed['access_token']))->getStatusCode());
    }

    private function registerTokenManager(
        \Symfony\Component\DependencyInjection\ContainerInterface $container,
        string $id,
        string $interface,
        string $class,
        array &$tokens
    ): void {
        $manager = $this->createMock($interface);
        $manager->method('createToken')->willReturnCallback(static fn () => new $class());
        $manager->method('updateToken')->willReturnCallback(static function (TokenInterface $token) use (&$tokens): void {
            $tokens[$token->getToken()] = $token;
        });
        $manager->method('findTokenByToken')->willReturnCallback(static function ($token) use (&$tokens) {
            return $tokens[$token] ?? null;
        });
        $manager->method('deleteToken')->willReturnCallback(static function (TokenInterface $token) use (&$tokens): void {
            unset($tokens[$token->getToken()]);
        });
        $container->set($id, $manager);
    }

    public static function protectedAction(): Response
    {
        return new Response('authenticated');
    }
}
