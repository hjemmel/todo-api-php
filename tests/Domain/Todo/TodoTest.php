<?php
declare(strict_types=1);

namespace Tests\Domain\Todo;

use App\Domain\Todo\Todo;
use Tests\TestCase;

class TodoTest extends TestCase
{
    public function todoProvider()
    {
        return [
            ["C137", 'Rick Sanchez', true],
            ["C138", 'Morty Smith', true],
            ["C139", 'Summer Smith', false],
            ["C140", 'Beth Smith', false],
            ["C141", 'Jerry Smith', false],
        ];
    }

    /**
     * @dataProvider todoProvider
     * @param $id
     * @param $name
     * @param $done
     */
    public function testGetters($id, $name, $done)
    {
        $todo = new Todo($id, $name, $done);

        $this->assertEquals($id, $todo->getId());
        $this->assertEquals($name, $todo->getName());
        $this->assertEquals($done, $todo->isDone());
    }

    /**
     * @dataProvider todoProvider
     * @param $id
     * @param $name
     * @param $done
     */
    public function testJsonSerialize($id, $name, $done)
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
