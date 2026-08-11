<?php
declare(strict_types=1);

namespace Tests\Application\Middleware;

use App\Application\Middleware\JsonBodyParserMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;
use Tests\TestCase;

class JsonBodyParserMiddlewareTest extends TestCase
{
    /**
     * A handler that records the request it was handed, so we can assert on what
     * the middleware passed through.
     */
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

    public function testDelegatesToTheNextHandler(): void
    {
        $seen = null;
        $request = $this->createRequest('GET', '/todos');

        $response = (new JsonBodyParserMiddleware())->process($request, $this->recordingHandler($seen));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertInstanceOf(Request::class, $seen);
    }

    public function testLeavesNonJsonRequestsUntouched(): void
    {
        $seen = null;
        $request = $this->createRequest('POST', '/todos', ['Content-Type' => 'application/x-www-form-urlencoded']);

        (new JsonBodyParserMiddleware())->process($request, $this->recordingHandler($seen));

        $this->assertNull($seen->getParsedBody());
    }

    public function testLeavesRequestsWithNoContentTypeUntouched(): void
    {
        $seen = null;
        $request = $this->createRequest('POST', '/todos', []);

        (new JsonBodyParserMiddleware())->process($request, $this->recordingHandler($seen));

        $this->assertNull($seen->getParsedBody());
    }

    /**
     * The middleware inspects the raw php://input stream, which is empty under the
     * CLI SAPI. An unparsable body must leave the parsed body alone rather than
     * setting it to null, so downstream actions can tell "absent" from "empty".
     */
    public function testDoesNotSetParsedBodyWhenJsonIsUnparsable(): void
    {
        $seen = null;
        $request = $this->createRequest('POST', '/todos', ['Content-Type' => 'application/json']);

        (new JsonBodyParserMiddleware())->process($request, $this->recordingHandler($seen));

        $this->assertNull($seen->getParsedBody());
    }

    public function testMatchesJsonContentTypeWithCharsetSuffix(): void
    {
        $seen = null;
        $request = $this->createRequest('POST', '/todos', ['Content-Type' => 'application/json; charset=utf-8']);

        $response = (new JsonBodyParserMiddleware())->process($request, $this->recordingHandler($seen));

        $this->assertSame(200, $response->getStatusCode());
    }
}
