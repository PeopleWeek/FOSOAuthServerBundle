<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->set('FOS\\OAuthServerBundle\\Security\\Authentication\\Authenticator\\OAuthAuthenticator', 'FOS\\OAuthServerBundle\\Security\\Authentication\\Authenticator\\OAuthAuthenticator')
        ->args([service('fos_oauth_server.server'), service('security.user_checker'), service('fos_oauth_server.user_provider')]);
};
