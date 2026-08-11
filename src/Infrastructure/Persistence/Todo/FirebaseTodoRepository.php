<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Todo;

use App\Domain\Todo\Todo;
use App\Domain\Todo\TodoInvalidNameException;
use App\Domain\Todo\TodoNotFoundException;
use App\Domain\Todo\TodoRepository;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Factory;

class FirebaseTodoRepository implements TodoRepository
{
    private DatabaseInterface $database;

    public function __construct(?DatabaseInterface $database = null)
    {
        if ($database === null) {
            $firebaseDb = (new Factory)
                ->withServiceAccount($_SERVER['DOCUMENT_ROOT'] . '/firebase-key.json')
                ->withDatabaseUri($_ENV['DATABASE_URI'])
                ->createDatabase();
            $this->database = new DatabaseWrapper($firebaseDb);
        } else {
            $this->database = $database;
        }
    }

    /**
     * {@inheritdoc}
     * @throws FirebaseException
     */
    public function findAll(): array
    {
        $todosFire = $this->database->getValue('/todos');

        $todos = [];

        if ($todosFire) {
            foreach ($todosFire as $key => $value) {
                $todos[] = new Todo($key, $value['name'], $value['done']);
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
        $this->assertTodoExists($id);

        $values = $this->database->getValue('/todos/' . $id);

        return new Todo($id, $values['name'], $values['done']);
    }

    /**
     * {@inheritdoc}
     * @throws FirebaseException
     */
    public function create(string $name, ?bool $done): Todo
    {
        $this->validateTodo($name);

        if (!isset($done)) {
            $done = false;
        }

        $key = $this->database->push('/todos', [
            'name' => $name,
            'done' => $done,
        ]);

        return new Todo($key, $name, $done);
    }

    /**
     * {@inheritdoc}
     * @throws FirebaseException
     */
    public function update(string $id, string $name, ?bool $done): Todo
    {
        $this->validateTodo($name);
        $this->assertTodoExists($id);

        if (!isset($done)) {
            $done = false;
        }

        $this->database->update('/todos/' . $id, [
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
        $this->assertTodoExists($id);

        $this->database->remove('/todos/' . $id);

        return $this->findAll();
    }

    /**
     * @throws TodoNotFoundException
     */
    private function assertTodoExists(string $id): void
    {
        if (!$this->database->exists('/todos/' . $id)) {
            throw new TodoNotFoundException();
        }
    }

    /**
     * @throws TodoInvalidNameException
     */
    private function validateTodo(string $name): void
    {
        if (empty(trim($name))) {
            throw new TodoInvalidNameException();
        }
    }
}
