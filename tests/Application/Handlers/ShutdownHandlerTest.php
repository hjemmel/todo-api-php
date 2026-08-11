<?php
declare(strict_types=1);

namespace Tests\Application\Handlers;

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Slim\CallableResolver;
use Slim\Psr7\Factory\ResponseFactory;
use Tests\TestCase;

/**
 * ShutdownHandler reads error_get_last() and writes straight to the output
 * stream, so each case runs in its own process and captures the emitted body.
 */
class ShutdownHandlerTest extends TestCase
{
    /**
     * Runs the handler over whatever the last PHP error was and returns the
     * decoded JSON it emitted.
     *
     * @return array<string, mixed>|null
     */
    private function emit(bool $displayErrorDetails): ?array
    {
        $handler = new ShutdownHandler(
            $this->createRequest('GET', '/todos'),
            new HttpErrorHandler(new CallableResolver(), new ResponseFactory()),
            $displayErrorDetails
        );

        ob_start();
        $handler();
        $output = ob_get_clean();

        return $output === '' ? null : json_decode($output, true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testFormatsUserWarnings(): void
    {
        @trigger_error('something odd', E_USER_WARNING);

        $payload = $this->emit(true);

        $this->assertSame(500, $payload['statusCode']);
        $this->assertSame('WARNING: something odd', $payload['error']['description']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testFormatsUserNotices(): void
    {
        @trigger_error('just so you know', E_USER_NOTICE);

        $payload = $this->emit(true);

        $this->assertSame('NOTICE: just so you know', $payload['error']['description']);
    }

    /**
     * Anything that is not a user-triggered warning or notice falls through to the
     * default branch, which appends the file and line.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testFormatsOtherErrorsWithFileAndLine(): void
    {
        @file_get_contents('/definitely/not/a/real/path');

        $payload = $this->emit(true);

        $this->assertStringStartsWith('ERROR: ', $payload['error']['description']);
        $this->assertStringContainsString(' on line ', $payload['error']['description']);
        $this->assertStringContainsString(' in file ', $payload['error']['description']);
    }

    /**
     * Stray output written before the shutdown handler runs (a stack trace, a
     * warning, a stray echo) must be discarded so the client gets clean JSON.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testDiscardsOutputBufferedBeforeEmitting(): void
    {
        @trigger_error('something odd', E_USER_WARNING);

        $handler = new ShutdownHandler(
            $this->createRequest('GET', '/todos'),
            new HttpErrorHandler(new CallableResolver(), new ResponseFactory()),
            true
        );

        ob_start();
        echo 'stray output that must not reach the client';
        $handler();
        $output = ob_get_clean();

        $this->assertStringNotContainsString('stray output', $output);
        $this->assertSame('WARNING: something odd', json_decode($output, true)['error']['description']);
    }

    /**
     * With error details switched off the user must never see the raw PHP error.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testHidesErrorDetailsWhenDisabled(): void
    {
        @trigger_error('leaky internal detail', E_USER_WARNING);

        $payload = $this->emit(false);

        $this->assertSame(
            'An error while processing your request. Please try again later.',
            $payload['error']['description']
        );
        $this->assertStringNotContainsString('leaky', $payload['error']['description']);
    }
}
