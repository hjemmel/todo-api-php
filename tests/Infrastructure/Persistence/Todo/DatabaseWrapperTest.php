<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Todo;

use App\Infrastructure\Persistence\Todo\DatabaseWrapper;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Kreait\Firebase\Database;
use Kreait\Firebase\Database\ApiClient;
use Kreait\Firebase\Database\UrlBuilder;
use Kreait\Firebase\Exception\DatabaseApiExceptionConverter;
use Kreait\Firebase\Http\ErrorResponseParser;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * DatabaseWrapper is the adapter between the app and the Firebase SDK, so the
 * interesting behaviour only shows up when the real SDK runs. Kreait's Reference
 * and ApiClient are final and cannot be mocked, but ApiClient accepts a Guzzle
 * ClientInterface — so we drive the genuine SDK against canned HTTP responses
 * rather than a live database or an emulator.
 */
class DatabaseWrapperTest extends TestCase
{
    private const DATABASE_URL = 'https://todo-test.firebaseio.com';

    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    /**
     * @param array<int, Response> $responses
     */
    private function wrapperFor(array $responses): DatabaseWrapper
    {
        $this->history = [];

        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $apiClient = new ApiClient(
            new Client(['handler' => $stack]),
            UrlBuilder::create(self::DATABASE_URL),
            new DatabaseApiExceptionConverter(new ErrorResponseParser())
        );

        return new DatabaseWrapper(new Database(new Uri(self::DATABASE_URL), $apiClient));
    }

    private function lastRequest(): RequestInterface
    {
        return $this->history[count($this->history) - 1]['request'];
    }

    public function testGetValueReturnsDecodedPayload(): void
    {
        $wrapper = $this->wrapperFor([
            new Response(200, [], json_encode([
                'C137' => ['name' => 'Rick Sanchez', 'done' => false],
            ])),
        ]);

        $value = $wrapper->getValue('/todos');

        $this->assertSame(['C137' => ['name' => 'Rick Sanchez', 'done' => false]], $value);
        $this->assertSame('GET', $this->lastRequest()->getMethod());
        $this->assertStringContainsString('/todos', $this->lastRequest()->getUri()->getPath());
    }

    public function testGetValueReturnsNullForAnEmptyPath(): void
    {
        $wrapper = $this->wrapperFor([new Response(200, [], 'null')]);

        $this->assertNull($wrapper->getValue('/todos'));
    }

    public function testExistsIsTrueWhenThePathHasAValue(): void
    {
        $wrapper = $this->wrapperFor([
            new Response(200, [], json_encode(['name' => 'Rick Sanchez', 'done' => false])),
        ]);

        $this->assertTrue($wrapper->exists('/todos/C137'));
    }

    public function testExistsIsFalseWhenThePathIsEmpty(): void
    {
        $wrapper = $this->wrapperFor([new Response(200, [], 'null')]);

        $this->assertFalse($wrapper->exists('/todos/C137'));
    }

    public function testPushReturnsTheGeneratedKey(): void
    {
        $wrapper = $this->wrapperFor([
            new Response(200, [], json_encode(['name' => '-NabcDEF123'])),
        ]);

        $key = $wrapper->push('/todos', ['name' => 'Rick Sanchez', 'done' => false]);

        $this->assertSame('-NabcDEF123', $key);

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            ['name' => 'Rick Sanchez', 'done' => false],
            json_decode((string) $request->getBody(), true)
        );
    }

    /**
     * Firebase generates the child key, so a response without one leaves the new
     * reference keyless. The wrapper must fail loudly rather than hand back null.
     */
    public function testPushFailsWhenNoKeyIsGenerated(): void
    {
        $wrapper = $this->wrapperFor([new Response(200, [], json_encode(['name' => '']))]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to push value at path "/"');

        $wrapper->push('/', ['name' => 'Rick Sanchez']);
    }

    public function testUpdateSendsAPatchWithTheGivenValues(): void
    {
        $wrapper = $this->wrapperFor([new Response(200, [], '{}')]);

        $wrapper->update('/todos/C137', ['name' => 'Morty Smith', 'done' => true]);

        $request = $this->lastRequest();
        $this->assertSame('PATCH', $request->getMethod());
        $this->assertStringContainsString('/todos/C137', $request->getUri()->getPath());
        $this->assertSame(
            ['name' => 'Morty Smith', 'done' => true],
            json_decode((string) $request->getBody(), true)
        );
    }

    public function testRemoveSendsADelete(): void
    {
        $wrapper = $this->wrapperFor([new Response(200, [], 'null')]);

        $wrapper->remove('/todos/C137');

        $request = $this->lastRequest();
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertStringContainsString('/todos/C137', $request->getUri()->getPath());
    }
}
