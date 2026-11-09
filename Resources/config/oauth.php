<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('fos_oauth_server.server.class', 'OAuth2\\OAuth2');
    $services = $container->services();
    $services->set('fos_oauth_server.storage.default', 'FOS\\OAuthServerBundle\\Storage\\OAuthStorage')
        ->args([
            service('fos_oauth_server.client_manager'),
            service('fos_oauth_server.access_token_manager'),
            service('fos_oauth_server.refresh_token_manager'),
            service('fos_oauth_server.auth_code_manager'),
            service('security.password_hasher_factory'),
            service('fos_oauth_server.user_provider')->nullOnInvalid(),
        ]);
    $services->set('fos_oauth_server.server', '%fos_oauth_server.server.class%')
        ->args([service('fos_oauth_server.storage'), '%fos_oauth_server.server.options%']);
    $services->set('FOS\\OAuthServerBundle\\Controller\\TokenController', 'FOS\\OAuthServerBundle\\Controller\\TokenController')
        ->args([service('fos_oauth_server.server')]);
    $services->alias('fos_oauth_server.controller.token', 'FOS\\OAuthServerBundle\\Controller\\TokenController')->public();
    $services->set('fos_oauth_server.clean_command', 'FOS\\OAuthServerBundle\\Command\\CleanCommand')
        ->args([
            service('fos_oauth_server.access_token_manager'),
            service('fos_oauth_server.refresh_token_manager'),
            service('fos_oauth_server.auth_code_manager'),
        ])
        ->tag('console.command');
    $services->set('fos_oauth_server.create_client_command', 'FOS\\OAuthServerBundle\\Command\\CreateClientCommand')
        ->args([service('fos_oauth_server.client_manager')])
        ->tag('console.command');
};
