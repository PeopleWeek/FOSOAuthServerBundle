<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->set('fos_oauth_server.client_manager.default', 'FOS\\OAuthServerBundle\\Document\\ClientManager')
        ->args([service('fos_oauth_server.document_manager'), '%fos_oauth_server.model.client.class%']);
    $services->set('fos_oauth_server.access_token_manager.default', 'FOS\\OAuthServerBundle\\Document\\AccessTokenManager')
        ->args([service('fos_oauth_server.document_manager'), '%fos_oauth_server.model.access_token.class%']);
    $services->set('fos_oauth_server.auth_code_manager.default', 'FOS\\OAuthServerBundle\\Document\\AuthCodeManager')
        ->args([service('fos_oauth_server.document_manager'), '%fos_oauth_server.model.auth_code.class%']);
    $services->set('fos_oauth_server.refresh_token_manager.default', 'FOS\\OAuthServerBundle\\Document\\RefreshTokenManager')
        ->args([service('fos_oauth_server.document_manager'), '%fos_oauth_server.model.refresh_token.class%']);
    $services->alias('FOS\\OAuthServerBundle\\Model\\ClientManagerInterface', 'fos_oauth_server.client_manager.default');
    $services->alias('FOS\\OAuthServerBundle\\Model\\AccessTokenManagerInterface', 'fos_oauth_server.access_token_manager.default');
    $services->alias('FOS\\OAuthServerBundle\\Model\\RefreshTokenManagerInterface', 'fos_oauth_server.refresh_token_manager.default');
    $services->alias('FOS\\OAuthServerBundle\\Model\\AuthCodeManagerInterface', 'fos_oauth_server.auth_code_manager.default');
};
