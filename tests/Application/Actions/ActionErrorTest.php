<?php
declare(strict_types=1);

namespace Tests\Application\Actions;

use App\Application\Actions\ActionError;
use PHPUnit\Framework\TestCase;

class ActionErrorTest extends TestCase
{
    public function testExposesTypeAndDescription(): void
    {
        $error = new ActionError(ActionError::VALIDATION_ERROR, 'Name is required');

        $this->assertSame(ActionError::VALIDATION_ERROR, $error->getType());
        $this->assertSame('Name is required', $error->getDescription());
    }

    public function testSettersAreFluentAndMutateState(): void
    {
        $error = new ActionError(ActionError::SERVER_ERROR, 'Boom');

        $this->assertSame($error, $error->setType(ActionError::BAD_REQUEST));
        $this->assertSame($error, $error->setDescription('Bad input'));

        $this->assertSame(ActionError::BAD_REQUEST, $error->getType());
        $this->assertSame('Bad input', $error->getDescription());
    }

    public function testSerializesTypeAndDescription(): void
    {
        $error = new ActionError(ActionError::NOT_ALLOWED, 'Use POST');

        $this->assertSame([
            'type' => ActionError::NOT_ALLOWED,
            'description' => 'Use POST',
        ], $error->jsonSerialize());
    }

    public function testEncodesToJson(): void
    {
        $error = new ActionError(ActionError::UNAUTHENTICATED, 'Log in first');

        $this->assertSame(
            '{"type":"UNAUTHENTICATED","description":"Log in first"}',
            json_encode($error)
        );
    }
}
