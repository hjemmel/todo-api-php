<?php
declare(strict_types=1);

namespace Tests\Application\Actions;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use PHPUnit\Framework\TestCase;

class ActionPayloadTest extends TestCase
{
    public function testDefaultsToOkWithNoDataOrError(): void
    {
        $payload = new ActionPayload();

        $this->assertSame(200, $payload->getStatusCode());
        $this->assertNull($payload->getData());
        $this->assertNull($payload->getError());
    }

    public function testExposesConstructorArguments(): void
    {
        $error = new ActionError(ActionError::BAD_REQUEST, 'Nope');
        $payload = new ActionPayload(400, ['id' => 'C137'], $error);

        $this->assertSame(400, $payload->getStatusCode());
        $this->assertSame(['id' => 'C137'], $payload->getData());
        $this->assertSame($error, $payload->getError());
    }

    public function testSerializesDataWhenPresent(): void
    {
        $payload = new ActionPayload(200, ['name' => 'Rick Sanchez']);

        $this->assertSame([
            'statusCode' => 200,
            'data' => ['name' => 'Rick Sanchez'],
        ], $payload->jsonSerialize());
    }

    /**
     * Data wins over error when both are set — only one key is ever emitted.
     */
    public function testDataTakesPrecedenceOverError(): void
    {
        $payload = new ActionPayload(200, ['ok' => true], new ActionError(ActionError::SERVER_ERROR, 'Boom'));

        $serialized = $payload->jsonSerialize();

        $this->assertArrayHasKey('data', $serialized);
        $this->assertArrayNotHasKey('error', $serialized);
    }

    public function testSerializesErrorWhenThereIsNoData(): void
    {
        $error = new ActionError(ActionError::RESOURCE_NOT_FOUND, 'Not here');
        $payload = new ActionPayload(404, null, $error);

        $this->assertSame([
            'statusCode' => 404,
            'error' => $error,
        ], $payload->jsonSerialize());
    }

    public function testSerializesOnlyStatusCodeWhenEmpty(): void
    {
        $this->assertSame(['statusCode' => 204], (new ActionPayload(204))->jsonSerialize());
    }

    public function testEncodesToJson(): void
    {
        $payload = new ActionPayload(404, null, new ActionError(ActionError::RESOURCE_NOT_FOUND, 'Not here'));

        $this->assertSame(
            '{"statusCode":404,"error":{"type":"RESOURCE_NOT_FOUND","description":"Not here"}}',
            json_encode($payload)
        );
    }
}
