<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Todo;

use App\Application\Actions\ActionPayload;
use App\Domain\Todo\TodoRepository;
use App\Domain\Todo\Todo;
use DI\Container;
use Tests\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ListTodoActionTest extends TestCase
{
    use ProphecyTrait;

    public function testAction()
    {
        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        $todo = new Todo("C137", 'Central Finite Curve', true);

        $todoRepositoryProphecy = $this->prophesize(TodoRepository::class);
        $todoRepositoryProphecy
            ->findAll()
            ->willReturn([$todo])
            ->shouldBeCalledOnce();

        $container->set(TodoRepository::class, $todoRepositoryProphecy->reveal());

        $request = $this->createRequest('GET', '/todos');
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, [$todo]);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
