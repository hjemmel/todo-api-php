<?php
declare(strict_types=1);

namespace Tests\Application\Middleware;

use App\Application\Middleware\SessionMiddleware;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;
use Tests\TestCase;

class SessionMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    private function recordingHandler(?Request &$seen): RequestHandler
    {
        return new class ($seen) implements RequestHandler {
            /** @param Request|null $seen */
            public function __construct(private mixed &$seen)
            {
            }

            public function handle(Request $request): Response
            {
                $this->seen = $request;

                return (new ResponseFactory())->createResponse(200);
            }
        };
    }

    public function testDoesNotStartASessionWithoutAnAuthorizationHeader(): void
    {
        $seen = null;
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $response = (new SessionMiddleware())->process($this->createRequest('GET', '/todos'), $this->recordingHandler($seen));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($seen->getAttribute('session'));
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    /**
     * session_start() mutates process-wide state and cannot be undone, so this
     * runs isolated to keep the rest of the suite clean.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testStartsASessionAndAttachesItWhenAuthorizationIsPresent(): void
    {
        $seen = null;
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token';

        $response = (new SessionMiddleware())->process($this->createRequest('GET', '/todos'), $this->recordingHandler($seen));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertIsArray($seen->getAttribute('session'));
    }
}
