<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Todo;

use App\Application\Actions\ActionPayload;
use App\Domain\Todo\TodoRepository;
use App\Domain\Todo\Todo;
use DI\Container;
use Tests\TestCase;

class ListTodoActionTest extends TestCase
{

    public function testAction()
    {
        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        $todo = new Todo("C137", 'Central Finite Curve', true);

        $todoRepositoryMock = $this->createMock(TodoRepository::class);
        $todoRepositoryMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$todo]);

        $container->set(TodoRepository::class, $todoRepositoryMock);

        $request = $this->createRequest('GET', '/todos');
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, [$todo]);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
