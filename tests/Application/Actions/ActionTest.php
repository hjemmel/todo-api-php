<?php
declare(strict_types=1);

namespace Tests\Application\Actions;

use App\Application\Actions\Action;
use App\Domain\Todo\TodoNotFoundException;
use Closure;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\NullLogger;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ResponseFactory;
use Tests\TestCase;

/**
 * Action is abstract, so these exercise the shared behaviour through a minimal
 * concrete subclass rather than through any one route.
 */
class ActionTest extends TestCase
{
    /**
     * @param Closure(ActionTestDouble): Response $action
     */
    private function invoke(Closure $action, array $args = []): Response
    {
        $double = new ActionTestDouble(new NullLogger(), $action);

        return $double(
            $this->createRequest('GET', '/todos'),
            (new ResponseFactory())->createResponse(),
            $args
        );
    }

    public function testResolveArgReturnsAKnownArgument(): void
    {
        $response = $this->invoke(
            fn (ActionTestDouble $a): Response => $a->respondWith(['id' => $a->arg('id')]),
            ['id' => 'C137']
        );

        $response->getBody()->rewind();
        $this->assertSame('C137', json_decode((string) $response->getBody(), true)['data']['id']);
    }

    public function testResolveArgRejectsAnUnknownArgument(): void
    {
        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage('Could not resolve argument `missing`.');

        $this->invoke(fn (ActionTestDouble $a): Response => $a->respondWith($a->arg('missing')));
    }

    /**
     * Domain "not found" errors are translated into HTTP 404 by the base class, so
     * actions never have to know about HTTP.
     */
    public function testDomainNotFoundBecomesHttpNotFound(): void
    {
        $this->expectException(HttpNotFoundException::class);

        $this->invoke(function (): Response {
            throw new TodoNotFoundException();
        });
    }

    public function testRespondWithDataWrapsPayloadAsJson(): void
    {
        $response = $this->invoke(fn (ActionTestDouble $a): Response => $a->respondWith(['name' => 'Rick Sanchez']));

        $response->getBody()->rewind();

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(
            ['statusCode' => 200, 'data' => ['name' => 'Rick Sanchez']],
            json_decode((string) $response->getBody(), true)
        );
    }
}

/**
 * Concrete Action that runs a supplied closure and exposes the protected helpers.
 */
class ActionTestDouble extends Action
{
    /** @var Closure(self): Response */
    private Closure $action;

    public function __construct(\Psr\Log\LoggerInterface $logger, Closure $action)
    {
        parent::__construct($logger);
        $this->action = $action;
    }

    protected function action(): Response
    {
        return ($this->action)($this);
    }

    public function arg(string $name): mixed
    {
        return $this->resolveArg($name);
    }

    public function respondWith(mixed $data): Response
    {
        return $this->respondWithData($data);
    }
}
