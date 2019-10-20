<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Todo;

use App\Domain\Todo\Todo;
use App\Domain\Todo\TodoInvalidNameException;
use App\Domain\Todo\TodoNotFoundException;
use App\Domain\Todo\TodoRepository;
use Kreait\Firebase\Database;
use Kreait\Firebase\Database\Reference;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Psr\Log\LoggerInterface;

class FirebaseTodoRepository implements TodoRepository
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * FirebaseTodoRepository constructor.
     *
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->database = (new Factory)
            ->withServiceAccount($_SERVER['DOCUMENT_ROOT'] . '/firebase-key.json')
            ->withDatabaseUri($_ENV["DATABASE_URI"])
            ->createDatabase();
    }

    /**
     * {@inheritdoc}
     * @throws FirebaseException
     */
    public function findAll(): array
    {
        $todosFire = $this->database->getReference("/todos")->getValue();

        $todos = [];

        if ($todosFire) {
            foreach ($todosFire as $key => $value) {
                array_push($todos, new Todo($key, $value["name"], $value["done"]));
            }
        }

        return array_values($todos);
    }

    /**
     * {@inheritdoc}
     * @throws FirebaseException
     */
    public function findTodoById(string $id): Todo
    {
        $todo = $this->getTodoById($id);

        $values = $todo->getValue();

        return new Todo($todo->getKey(), $values["name"], $values["done"]);
    }

    /**
     * {@inheritdoc}
     * @throws FirebaseException
     */
    public function create(string $name, bool $done): Todo
    {
        $this->validateTodo($name);

        if (!isset($done)) {
            $done = false;
        }

        $todos = $this->database->getReference("/todos");

        $newTodo = $todos->push([
            'name' => $name,
            'done' => $done,
        ]);

        return new Todo($newTodo->getKey(), $name, $done);
    }

    /**
     * {@inheritdoc}
     * @throws FirebaseException
     */
    public function update(string $id, string $name, bool $done): Todo
    {
        $this->validateTodo($name);

        $todo = $this->getTodoById($id);

        if (!isset($done)) {
            $done = false;
        }

        $todo->update([
            'name' => $name,
            'done' => $done,
        ]);

        return new Todo($id, $name, $done);
    }

    /**
     * {@inheritdoc}
     * @throws FirebaseException
     */
    public function deleteTodoById(string $id): array
    {
        $todo = $this->getTodoById($id);

        $todo->remove();
        return $this->findAll();
    }

    /**
     * @param string $id
     * @return Reference
     * @throws FirebaseException
     * @throws TodoNotFoundException
     */
    private function getTodoById(string $id)
    {
        $todo = $this->database->getReference("/todos/" . $id);

        $exists = $todo->getSnapshot()->exists();

        if (!isset($exists) || !$exists) {
            throw new TodoNotFoundException();
        }

        return $todo;
    }

    /**
     * @param string $name
     * @throws TodoInvalidNameException
     */
    private function validateTodo(string $name)
    {
        if (empty(trim($name))) {
            throw new TodoInvalidNameException();
        }
    }
}
