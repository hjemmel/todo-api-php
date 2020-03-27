<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Todo;

use App\Domain\Todo\Todo;
use App\Domain\Todo\TodoInvalidNameException;
use App\Domain\Todo\TodoNotFoundException;
use App\Infrastructure\Persistence\Todo\FirebaseTodoRepository;
use Kreait\Firebase\Database;
use Kreait\Firebase\Database\Reference;
use Kreait\Firebase\Database\Snapshot;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FirebaseTodoRepositoryTest extends TestCase
{

    /**
     * @var Database|\MockObject
     */
    private $database;

    /** @var Reference|MockObject */
    private $ref;

    protected function setUp(): void
    {
        $this->database = $this->createMock(Database::class);
        $this->ref = $this->createMock(Reference::class);

        $this->database
            ->method('getReference')
            ->willReturn($this->ref);

        $_SERVER['DOCUMENT_ROOT'] = __DIR__;
        $_ENV['$_SERVER'] = "http://domain.tld/";

    }

    public function testFindAll()
    {
        $this->ref
            ->method("getValue")
            ->willReturn([
                "C137" => [
                    "name"=> "Rick Sanchez",
                    "done"=> false
                ]
            ]);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todos = $todoRepository->findAll();

        $this->assertEquals([new Todo("C137", 'Rick Sanchez', false)], $todos);
    }

    public function testFindTodoOfId()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $this->ref
            ->method("getSnapshot")
            ->willReturn($snapshot);

        $snapshot
            ->method("exists")
            ->willReturn(true);

        $this->ref
            ->method("getValue")
            ->willReturn(
                [
                    "name"=> "Rick Sanchez",
                    "done"=> false
                ]);

        $this->ref
            ->method("getKey")
            ->willReturn("C137");

        $todo = new Todo("C137", 'Rick Sanchez', false);

        $todoRepository = $todoRepository = new FirebaseTodoRepository($this->database);

        $this->assertEquals($todo, $todoRepository->findTodoById("C137"));
    }

    public function testFindTodoByIdThrowsNotFoundException()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $this->ref
            ->method("getSnapshot")
            ->willReturn($snapshot);

        $snapshot
            ->method("exists")
            ->willReturn(false);

        $this->expectException(TodoNotFoundException::class);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todoRepository->findTodoById("C137");
    }

    private function updateMock()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $this->ref
            ->method("getSnapshot")
            ->willReturn($snapshot);

        $snapshot
            ->method("exists")
            ->willReturn(true);

        $this->ref
            ->method("getValue")
            ->willReturn(
                [
                    "name"=> "Morty Smith",
                    "done"=> false
                ]);

        $this->ref
            ->method("getKey")
            ->willReturn("C137");
    }
    public function testUpdateTodo()
    {
        $this->updateMock();

        $todoRepository = new FirebaseTodoRepository($this->database);

        $expectedTodo = new Todo('C137', 'Morty Smith', true);

        $this->assertEquals($expectedTodo, $todoRepository->update('C137', 'Morty Smith', true));
    }

    public function testUpdateTodoDefaultDone()
    {
        $this->updateMock();

        $todoRepository = new FirebaseTodoRepository($this->database);

        $expectedTodo = new Todo('C137', 'Morty Smith', false);

        $this->assertEquals($expectedTodo, $todoRepository->update('C137', 'Morty Smith', null));
    }

    public function testUpdateThrowsNotFoundException()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $this->ref
            ->method("getSnapshot")
            ->willReturn($snapshot);

        $snapshot
            ->method("exists")
            ->willReturn(false);

        $this->expectException(TodoNotFoundException::class);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todoRepository->update('C137', 'Rick Sanchez', false);
    }

    public function testCreateTodo()
    {
        $todoRepository = new FirebaseTodoRepository($this->database);

        $snapshot = $this->createMock(Snapshot::class);
        $this->ref
            ->method("getSnapshot")
            ->willReturn($snapshot);

        $snapshot
            ->method("exists")
            ->willReturn(true);

        $this->ref
            ->method("push")
            ->willReturn($this->ref);

        $this->ref
            ->method("getKey")
            ->willReturn("C137");

        $todo = $todoRepository->create('Rick Sanchez', true);

        $this->assertEquals($todo->getName(), "Rick Sanchez");
        $this->assertTrue($todo->isDone());
        $this->assertIsString($todo->getId());
    }

    public function testCreateTodoDefaultDone()
    {
        $todoRepository = new FirebaseTodoRepository($this->database);

        $snapshot = $this->createMock(Snapshot::class);
        $this->ref
            ->method("getSnapshot")
            ->willReturn($snapshot);

        $snapshot
            ->method("exists")
            ->willReturn(true);

        $this->ref
            ->method("push")
            ->willReturn($this->ref);

        $this->ref
            ->method("getKey")
            ->willReturn("C137");

        $todo = $todoRepository->create('Rick Sanchez', null);

        $this->assertEquals($todo->getName(), "Rick Sanchez");
        $this->assertFalse($todo->isDone());
        $this->assertIsString($todo->getId());
    }

    public function testCreateTodoEmptyName()
    {
        $this->expectException(TodoInvalidNameException::class);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todoRepository->create('', null);
    }

    public function testDeleteTodo()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $this->ref
            ->method("getSnapshot")
            ->willReturn($snapshot);

        $snapshot
            ->method("exists")
            ->willReturn(true);

        $this->ref
            ->method("remove")
            ->willReturn($this->ref);

        $this->ref
            ->method("getValue")
            ->willReturn([
                "C137" => [
                    "name"=> "Rick Sanchez",
                    "done"=> false
                ]
            ]);

        $todoRepository = new FirebaseTodoRepository($this->database);

        $todosAfterDelete = $todoRepository->deleteTodoById("C137");

        $this->assertEquals(1, count($todosAfterDelete));
    }
}
