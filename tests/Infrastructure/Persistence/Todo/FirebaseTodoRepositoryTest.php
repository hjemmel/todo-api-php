<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Todo;

use App\Domain\Todo\Todo;
use App\Infrastructure\Persistence\Todo\FirebaseTodoRepository;
use Kreait\Firebase\Database;
use Kreait\Firebase\Database\Reference;
use Kreait\Firebase\Database\Snapshot;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FirebaseTodoRepositoryTest extends TestCase
{

    /**
     * @var Database|\PHPUnit_Framework_MockObject_MockObject
     */
    private $database;

    /** @var Reference|MockObject */
    private $ref;

    protected function setUp()
    {
        parent::setUp();

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

    /**
     * @expectedException \App\Domain\Todo\TodoNotFoundException
     */
    public function testFindTodoByIdThrowsNotFoundException()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $this->ref
            ->method("getSnapshot")
            ->willReturn($snapshot);

        $snapshot
            ->method("exists")
            ->willReturn(false);

        $todoRepository = new FirebaseTodoRepository($this->database);
        $todoRepository->findTodoById("C137");
    }

}
