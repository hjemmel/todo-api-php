<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Todo;

use App\Domain\Todo\Todo;
use App\Domain\Todo\TodoInvalidNameException;
use App\Domain\Todo\TodoNotFoundException;
use App\Infrastructure\Persistence\Todo\DatabaseInterface;
use App\Infrastructure\Persistence\Todo\FirebaseTodoRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FirebaseTodoRepositoryTest extends TestCase
{
    /** @var DatabaseInterface&MockObject */
    private DatabaseInterface $database;

    protected function setUp(): void
    {
        $this->database = $this->createMock(DatabaseInterface::class);

        $_SERVER['DOCUMENT_ROOT'] = __DIR__;
        $_ENV['DATABASE_URI'] = 'http://domain.tld/';
    }

    public function testFindAll(): void
    {
        $this->database
            ->expects($this->once())
            ->method('getValue')
            ->with('/todos')
            ->willReturn([
                'C137' => [
                    'name' => 'Rick Sanchez',
                    'done' => false,
                ],
            ]);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todos = $todoRepository->findAll();

        $this->assertEquals([new Todo('C137', 'Rick Sanchez', false)], $todos);
    }

    public function testFindTodoOfId(): void
    {
        $this->database
            ->expects($this->once())
            ->method('exists')
            ->with('/todos/C137')
            ->willReturn(true);

        $this->database
            ->expects($this->once())
            ->method('getValue')
            ->with('/todos/C137')
            ->willReturn([
                'name' => 'Rick Sanchez',
                'done' => false,
            ]);

        $todo = new Todo('C137', 'Rick Sanchez', false);
        $todoRepository = new FirebaseTodoRepository($this->database);

        $this->assertEquals($todo, $todoRepository->findTodoById('C137'));
    }

    public function testFindTodoByIdThrowsNotFoundException(): void
    {
        $this->database
            ->expects($this->once())
            ->method('exists')
            ->with('/todos/C137')
            ->willReturn(false);

        $this->expectException(TodoNotFoundException::class);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todoRepository->findTodoById('C137');
    }

    public function testUpdateTodo(): void
    {
        $this->database
            ->expects($this->once())
            ->method('exists')
            ->with('/todos/C137')
            ->willReturn(true);

        $this->database
            ->expects($this->once())
            ->method('update')
            ->with('/todos/C137', [
                'name' => 'Morty Smith',
                'done' => true,
            ]);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $expectedTodo = new Todo('C137', 'Morty Smith', true);

        $this->assertEquals($expectedTodo, $todoRepository->update('C137', 'Morty Smith', true));
    }

    public function testUpdateTodoDefaultDone(): void
    {
        $this->database
            ->expects($this->once())
            ->method('exists')
            ->with('/todos/C137')
            ->willReturn(true);

        $this->database
            ->expects($this->once())
            ->method('update')
            ->with('/todos/C137', [
                'name' => 'Morty Smith',
                'done' => false,
            ]);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $expectedTodo = new Todo('C137', 'Morty Smith', false);

        $this->assertEquals($expectedTodo, $todoRepository->update('C137', 'Morty Smith', null));
    }

    public function testUpdateThrowsNotFoundException(): void
    {
        $this->database
            ->expects($this->once())
            ->method('exists')
            ->with('/todos/C137')
            ->willReturn(false);

        $this->expectException(TodoNotFoundException::class);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todoRepository->update('C137', 'Rick Sanchez', false);
    }

    public function testCreateTodo(): void
    {
        $this->database
            ->expects($this->once())
            ->method('push')
            ->with('/todos', [
                'name' => 'Rick Sanchez',
                'done' => true,
            ])
            ->willReturn('C137');

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todo = $todoRepository->create('Rick Sanchez', true);

        $this->assertEquals('Rick Sanchez', $todo->getName());
        $this->assertTrue($todo->isDone());
        $this->assertSame('C137', $todo->getId());
    }

    public function testCreateTodoDefaultDone(): void
    {
        $this->database
            ->expects($this->once())
            ->method('push')
            ->with('/todos', [
                'name' => 'Rick Sanchez',
                'done' => false,
            ])
            ->willReturn('C137');

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todo = $todoRepository->create('Rick Sanchez', null);

        $this->assertEquals('Rick Sanchez', $todo->getName());
        $this->assertFalse($todo->isDone());
        $this->assertSame('C137', $todo->getId());
    }

    public function testCreateTodoEmptyName(): void
    {
        // The name is validated before the database is touched.
        $this->database
            ->expects($this->never())
            ->method('push');

        $this->expectException(TodoInvalidNameException::class);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todoRepository->create('', null);
    }

    public function testDeleteTodo(): void
    {
        $this->database
            ->expects($this->once())
            ->method('exists')
            ->with('/todos/C137')
            ->willReturn(true);

        $this->database
            ->expects($this->once())
            ->method('remove')
            ->with('/todos/C137');

        $this->database
            ->expects($this->once())
            ->method('getValue')
            ->with('/todos')
            ->willReturn([
                'C137' => [
                    'name' => 'Rick Sanchez',
                    'done' => false,
                ],
            ]);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todosAfterDelete = $todoRepository->deleteTodoById('C137');

        $this->assertCount(1, $todosAfterDelete);
    }
}
