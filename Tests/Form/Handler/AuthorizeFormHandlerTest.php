<?php

declare(strict_types=1);

namespace FOS\OAuthServerBundle\Tests\Form\Handler;

use FOS\OAuthServerBundle\Form\Handler\AuthorizeFormHandler;
use FOS\OAuthServerBundle\Form\Model\Authorize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthorizeFormHandlerTest extends TestCase
{
    public function testRejectsInvalidRequestArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AuthorizeFormHandler($this->createMock(FormInterface::class), new \stdClass());
    }

    public function testMissingCurrentRequestDoesNotProcessForm(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())->method('setData');
        self::assertFalse((new AuthorizeFormHandler($form, new RequestStack()))->process());
    }

    public function testGetInitializesFormWithoutSubmitting(): void
    {
        $request = Request::create('/authorize?client_id=test&scope=api');
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())->method('setData')->with(new Authorize(false, $request->query->all()))->willReturnSelf();
        $form->expects(self::never())->method('handleRequest');
        self::assertFalse((new AuthorizeFormHandler($form, $request))->process());
    }

    /** @dataProvider submissionStates */
    public function testPostRequiresSubmittedValidForm(bool $submitted, bool $valid, bool $expected): void
    {
        $previousGet = $_GET;
        try {
            $request = Request::create('/authorize?client_id=test&scope=api', 'POST', ['accepted' => 'yes']);
            $stack = new RequestStack();
            $stack->push($request);
            $data = new Authorize(true, $request->query->all());
            $form = $this->createMock(FormInterface::class);
            $form->expects(self::once())->method('setData')->with($data)->willReturnSelf();
            $form->expects(self::once())->method('handleRequest')->with($request)->willReturnSelf();
            $form->method('isSubmitted')->willReturn($submitted);
            $form->method('isValid')->willReturn($valid);
            $form->method('getData')->willReturn($data);
            $handler = new AuthorizeFormHandler($form, $stack);

            self::assertSame($expected, $handler->process());
            self::assertTrue($handler->isAccepted());
            self::assertFalse($handler->isRejected());
            self::assertSame('api', $handler->getScope());
            if ($expected) {
                self::assertSame('test', $_GET['client_id']);
                self::assertSame('api', $_GET['scope']);
            }
        } finally {
            $_GET = $previousGet;
        }
    }

    public static function submissionStates(): array
    {
        return [[false, false, false], [true, false, false], [true, true, true]];
    }

    public function testContainerFallbackStillAcceptsARequest(): void
    {
        $container = new Container();
        $container->set('request', Request::create('/authorize'));
        $handler = new AuthorizeFormHandler($this->createMock(FormInterface::class));
        $handler->setContainer($container);
        self::assertFalse($handler->process());
        $handler->setContainer(null);
    }
}
