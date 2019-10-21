<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Todo;

use App\Domain\Todo\Todo;
use App\Infrastructure\Persistence\Todo\FirebaseTodoRepository;
use GuzzleHttp\Psr7\Uri;
use Kreait\Firebase\Database;
use Kreait\Firebase\Database\Reference;
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


}
