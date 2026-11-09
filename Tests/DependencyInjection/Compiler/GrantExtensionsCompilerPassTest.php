<?php

declare(strict_types=1);

namespace FOS\OAuthServerBundle\Tests\DependencyInjection\Compiler;

use FOS\OAuthServerBundle\DependencyInjection\Compiler\GrantExtensionsCompilerPass;
use FOS\OAuthServerBundle\Storage\OAuthStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class GrantExtensionsCompilerPassTest extends TestCase
{
    public function testCustomStorageWithoutExtensionSupportIsUnchanged(): void
    {
        $container = new ContainerBuilder();
        $storage = $container->register('fos_oauth_server.storage', \stdClass::class);
        (new GrantExtensionsCompilerPass())->process($container);
        self::assertSame([], $storage->getMethodCalls());
    }

    public function testRegistersEveryTaggedGrantExtension(): void
    {
        $container = new ContainerBuilder();
        $storage = $container->register('fos_oauth_server.storage', OAuthStorage::class);
        $container->register('first')->addTag('fos_oauth_server.grant_extension', ['uri' => 'one'])
            ->addTag('fos_oauth_server.grant_extension', ['uri' => 'two']);
        $container->register('second')->addTag('fos_oauth_server.grant_extension', ['uri' => 'three']);
        (new GrantExtensionsCompilerPass())->process($container);
        self::assertEquals([
            ['setGrantExtension', ['one', new Reference('first')]],
            ['setGrantExtension', ['two', new Reference('first')]],
            ['setGrantExtension', ['three', new Reference('second')]],
        ], $storage->getMethodCalls());
    }

    public function testGrantExtensionRequiresAUri(): void
    {
        $container = new ContainerBuilder();
        $container->register('fos_oauth_server.storage', OAuthStorage::class);
        $container->register('invalid')->addTag('fos_oauth_server.grant_extension');
        $this->expectException(InvalidArgumentException::class);
        (new GrantExtensionsCompilerPass())->process($container);
    }
}
