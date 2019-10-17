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
use function GuzzleHttp\Psr7\stream_for;

class UpdateActionTest extends TestCase
{
    public function testAction()
    {
        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        $todo = new Todo("C137", 'Central Finite Curve', true);

        $todoRepositoryProphecy = $this->prophesize(TodoRepository::class);
        $todoRepositoryProphecy
            ->update('C137', 'Central Finite Curve', false)
            ->willReturn($todo)
            ->shouldBeCalledOnce();

        $container->set(TodoRepository::class, $todoRepositoryProphecy->reveal());

        $request = $this->createRequest('PUT', '/todos/C137');
        $request = $request->withParsedBody([
            'name' => 'Central Finite Curve',
            'done' => false
        ]);
        $response = $app->handle($request);

        $payload = (string)$response->getBody();
        $expectedPayload = new ActionPayload(200, $todo);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
