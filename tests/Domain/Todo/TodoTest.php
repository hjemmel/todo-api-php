<?php

declare(strict_types=1);

namespace Tests\Domain\Todo;

use App\Domain\Todo\Todo;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TodoTest extends TestCase
{
    public static function todoProvider(): array
    {
        return [
            ["C137", 'Rick Sanchez', true],
            ["C138", 'Morty Smith', true],
            ["C139", 'Summer Smith', false],
            ["C140", 'Beth Smith', false],
            ["C141", 'Jerry Smith', false],
        ];
    }

    #[DataProvider('todoProvider')]
    public function testGetters(string $id, string $name, bool $done): void
    {
        $todo = new Todo($id, $name, $done);

        $this->assertEquals($id, $todo->getId());
        $this->assertEquals($name, $todo->getName());
        $this->assertEquals($done, $todo->isDone());
    }

    #[DataProvider('todoProvider')]
    public function testJsonSerialize(string $id, string $name, bool $done): void
    {
        $todo = new Todo($id, $name, $done);

        $expectedPayload = json_encode([
            'id' => $id,
            'name' => $name,
            'done' => $done
        ]);

        $this->assertEquals($expectedPayload, json_encode($todo));
    }
}
