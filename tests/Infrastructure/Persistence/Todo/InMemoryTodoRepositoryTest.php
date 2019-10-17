<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Todo;

use App\Domain\Todo\Todo;
use App\Domain\Todo\TodoNotFoundException;
use App\Infrastructure\Persistence\Todo\InMemoryTodoRepository;
use Tests\TestCase;

class InMemoryTodoRepositoryTest extends TestCase
{
    public function testFindAll()
    {
        $todo = new Todo("C137", 'Rick Sanchez', false);

        $todoRepository = new InMemoryTodoRepository(["C137" => $todo]);

        $this->assertEquals([$todo], $todoRepository->findAll());
    }

    public function testFindTodoOfId()
    {
        $todo = new Todo("C137", 'Rick Sanchez', false);

        $todoRepository = new InMemoryTodoRepository(["C137" => $todo]);

        $this->assertEquals($todo, $todoRepository->findTodoById("C137"));
    }

    /**
     * @expectedException \App\Domain\Todo\TodoNotFoundException
     */
    public function testFindTodoByIdThrowsNotFoundException()
    {
        $todoRepository = new InMemoryTodoRepository([]);
        $todoRepository->findTodoById("C137");
    }

    public function testUpdateTodo()
    {
        $oldTodo = new Todo('C137', 'Rick Sanchez', false);

        $todoRepository = new InMemoryTodoRepository(["C137" => $oldTodo]);

        $todoRepository->update('C137', 'Morty Smith', true);

        $expectedTodo = new Todo('C137', 'Morty Smith', true);

        $this->assertEquals($expectedTodo, $todoRepository->findTodoById("C137"));
    }

    /**
     * @expectedException \App\Domain\Todo\TodoNotFoundException
     */
    public function testUpdateThrowsNotFoundException()
    {
        $todoRepository = new InMemoryTodoRepository([]);
        $todoRepository->update('C137', 'Rick Sanchez', false);
    }

    public function testCreateTodo()
    {
        $todoRepository = new InMemoryTodoRepository([]);

        $todo = $todoRepository->create('Rick Sanchez', true);

        $this->assertEquals($todo->getName(), "Rick Sanchez");
        $this->assertTrue($todo->isDone());
        $this->assertIsString($todo->getId());
    }

    public function testDeleteTodo()
    {
        $todos = [
            '1' => new Todo('1', 'Buy apples', false),
            '2' => new Todo('2', 'more memory for mac', false),
            '3' => new Todo('3', 'change visa', true),
            '4' => new Todo('4', 'see joker', true),
            '5' => new Todo('5', 'interview', false),
        ];

        $todoRepository = new InMemoryTodoRepository($todos);

        $todosAfterDelete = $todoRepository->deleteTodoById("3");

        $this->assertEquals(4, count($todosAfterDelete));
    }
}
