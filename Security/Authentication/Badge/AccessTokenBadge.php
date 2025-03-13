<?php

declare(strict_types=1);

namespace FOS\OAuthServerBundle\Security\Authentication\Badge;

use FOS\OAuthServerBundle\Model\AccessToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class AccessTokenBadge implements BadgeInterface
{
    public function __construct(
        private readonly AccessToken $accessToken,
        private readonly array $roles
    ) {}

    public function getAccessToken(): AccessToken
    {
        return $this->accessToken;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isResolved(): bool
    {
        return true;
    }
}