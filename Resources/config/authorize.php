<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->set('fos_oauth_server.authorize.form', 'Symfony\\Component\\Form\\Form')
        ->args(['%fos_oauth_server.authorize.form.name%', '%fos_oauth_server.authorize.form.type%', null, ['validation_groups' => '%fos_oauth_server.authorize.form.validation_groups%']]);
    $services->set('fos_oauth_server.authorize.form.type', 'FOS\\OAuthServerBundle\\Form\\Type\\AuthorizeFormType')
        ->tag('form.type', ['alias' => 'fos_oauth_server_authorize']);
    $services->set('fos_oauth_server.authorize.form.handler.default', 'FOS\\OAuthServerBundle\\Form\\Handler\\AuthorizeFormHandler')
        ->args([service('fos_oauth_server.authorize.form'), service('request_stack')]);
    $services->set('fos_oauth_server.controller.authorize', 'FOS\\OAuthServerBundle\\Controller\\AuthorizeController')
        ->args([
            service('request_stack'),
            service('fos_oauth_server.authorize.form'),
            service('fos_oauth_server.authorize.form.handler'),
            service('fos_oauth_server.server'),
            service('security.token_storage'),
            service('router'),
            service('fos_oauth_server.client_manager'),
            service('event_dispatcher'),
            service('twig'),
        ])->public();
};
