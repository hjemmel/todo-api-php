<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Todo;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Application\Handlers\HttpErrorHandler;
use App\Domain\Todo\Todo;
use App\Domain\Todo\TodoNotFoundException;
use App\Domain\Todo\TodoRepository;
use DI\Container;
use Slim\Middleware\ErrorMiddleware;
use Tests\TestCase;

class DeleteActionTest extends TestCase
{

    public function testAction()
    {
        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        $todos = [new Todo("C137", 'Central Finite Curve', true)];

        $todoRepositoryMock = $this->createMock(TodoRepository::class);
        $todoRepositoryMock
            ->expects($this->once())
            ->method('deleteTodoById')
            ->with("C133")
            ->willReturn($todos);

        $container->set(TodoRepository::class, $todoRepositoryMock);

        $request = $this->createRequest('DELETE', '/todos/C133');
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $todos);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testActionThrowsTodoNotFoundException()
    {
        $app = $this->getAppInstance();

        $callableResolver = $app->getCallableResolver();
        $responseFactory = $app->getResponseFactory();

        $errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);
        $errorMiddleware = new ErrorMiddleware($callableResolver, $responseFactory, true, false, false);
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        $app->add($errorMiddleware);

        /** @var Container $container */
        $container = $app->getContainer();

        $todoRepositoryMock = $this->createMock(TodoRepository::class);
        $todoRepositoryMock
            ->expects($this->once())
            ->method('deleteTodoById')
            ->with("C666")
            ->willThrowException(new TodoNotFoundException());

        $container->set(TodoRepository::class, $todoRepositoryMock);

        $request = $this->createRequest('DELETE', '/todos/C666');
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, 'The todo you requested does not exist.');
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
