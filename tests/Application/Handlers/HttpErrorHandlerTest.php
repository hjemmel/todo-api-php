<?php
declare(strict_types=1);

namespace Tests\Application\Handlers;

use App\Application\Actions\ActionError;
use App\Application\Handlers\HttpErrorHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\CallableResolver;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Psr7\Factory\ResponseFactory;
use Tests\TestCase;
use Throwable;

class HttpErrorHandlerTest extends TestCase
{
    private HttpErrorHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new HttpErrorHandler(new CallableResolver(), new ResponseFactory());
    }

    /**
     * Runs the handler and returns the decoded JSON body alongside the response.
     *
     * @return array{0: \Psr\Http\Message\ResponseInterface, 1: array<string, mixed>}
     */
    private function handle(Throwable $exception, bool $displayErrorDetails = false): array
    {
        $request = $this->createRequest('GET', '/todos');

        $response = ($this->handler)($request, $exception, $displayErrorDetails, false, false);
        $response->getBody()->rewind();

        return [$response, json_decode((string) $response->getBody(), true)];
    }

    /**
     * Each Slim HTTP exception maps to its own ActionError type and status code.
     *
     * @return array<string, array{0: class-string<HttpException>, 1: int, 2: string}>
     */
    public static function httpExceptionProvider(): array
    {
        return [
            'not found' => [HttpNotFoundException::class, 404, ActionError::RESOURCE_NOT_FOUND],
            'method not allowed' => [HttpMethodNotAllowedException::class, 405, ActionError::NOT_ALLOWED],
            'unauthorized' => [HttpUnauthorizedException::class, 401, ActionError::UNAUTHENTICATED],
            'forbidden' => [HttpForbiddenException::class, 403, ActionError::INSUFFICIENT_PRIVILEGES],
            'bad request' => [HttpBadRequestException::class, 400, ActionError::BAD_REQUEST],
            'not implemented' => [HttpNotImplementedException::class, 501, ActionError::NOT_IMPLEMENTED],
        ];
    }

    /**
     * @param class-string<HttpException> $exceptionClass
     */
    #[DataProvider('httpExceptionProvider')]
    public function testHttpExceptionMapsToStatusCodeAndErrorType(
        string $exceptionClass,
        int $expectedStatus,
        string $expectedType
    ): void {
        $request = $this->createRequest('GET', '/todos');
        $exception = new $exceptionClass($request, 'Something went wrong');

        [$response, $payload] = $this->handle($exception);

        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame($expectedStatus, $payload['statusCode']);
        $this->assertSame($expectedType, $payload['error']['type']);
        $this->assertSame('Something went wrong', $payload['error']['description']);
    }

    public function testResponseIsJson(): void
    {
        $request = $this->createRequest('GET', '/todos');

        [$response] = $this->handle(new HttpNotFoundException($request));

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testGenericExceptionIsReportedAsServerError(): void
    {
        [$response, $payload] = $this->handle(new RuntimeException('Database exploded'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(ActionError::SERVER_ERROR, $payload['error']['type']);
    }

    public function testGenericExceptionHidesDetailsByDefault(): void
    {
        [, $payload] = $this->handle(new RuntimeException('Database exploded'), false);

        $this->assertSame(
            'An internal error has occurred while processing your request.',
            $payload['error']['description']
        );
        $this->assertStringNotContainsString('exploded', $payload['error']['description']);
    }

    public function testGenericExceptionExposesDetailsWhenEnabled(): void
    {
        [, $payload] = $this->handle(new RuntimeException('Database exploded'), true);

        $this->assertSame('Database exploded', $payload['error']['description']);
    }

    /**
     * displayErrorDetails must not leak details for HTTP exceptions, which carry
     * their own user-facing message.
     */
    public function testHttpExceptionKeepsItsOwnMessageWhenDetailsEnabled(): void
    {
        $request = $this->createRequest('GET', '/todos');
        $exception = new HttpNotFoundException($request, 'No such todo');

        [, $payload] = $this->handle($exception, true);

        $this->assertSame('No such todo', $payload['error']['description']);
    }
}
