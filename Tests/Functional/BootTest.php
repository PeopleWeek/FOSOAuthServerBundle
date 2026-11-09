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

namespace FOS\OAuthServerBundle\Tests\Functional;

class BootTest extends TestCase
{
    /**
     * @dataProvider getTestBootData
     *
     * @param string $env
     */
    public function testBoot($env): void
    {
        try {
            $kernel = static::bootKernel(['env' => $env]);

            self::assertInstanceOf(\FOS\OAuthServerBundle\Controller\AuthorizeController::class, $kernel->getContainer()->get('fos_oauth_server.controller.authorize'));
        } catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @return array<mixed>
     */
    public static function getTestBootData(): array
    {
        return [
            ['orm'],
        ];
    }
}
