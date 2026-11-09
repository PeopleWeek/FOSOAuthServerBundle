<?php

declare(strict_types=1);

/*
 * This file is part of the FOSOAuthServerBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\OAuthServerBundle\Tests;

use FOS\OAuthServerBundle\DependencyInjection\Compiler;
use FOS\OAuthServerBundle\FOSOAuthServerBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FOSOAuthServerBundleTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testConstruction(): void
    {
        $bundle = new FOSOAuthServerBundle();

        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder->expects($this->exactly(2))
            ->method('addCompilerPass')
            ->withConsecutive(
                [new Compiler\GrantExtensionsCompilerPass()],
                [new Compiler\RequestStackCompilerPass()]
            )
            ->willReturnSelf();

        $this->assertNull($bundle->build($containerBuilder));
    }
}
