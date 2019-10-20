<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Todo;

use App\Domain\Todo\Todo;
use App\Domain\Todo\TodoInvalidNameException;
use App\Domain\Todo\TodoNotFoundException;
use App\Domain\Todo\TodoRepository;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class InMemoryTodoRepository implements TodoRepository
{
    /**
     * @var Todo[]
     */
    private $todos;

    /**
     * InMemoryTodoRepository constructor.
     *
     * @param array|null $todos
     */
    public function __construct(array $todos = null)
    {
        $this->todos = $todos ?? [
                '1' => new Todo('1', 'Buy apples', false),
                '2' => new Todo('2', 'more memory for mac', false),
                '3' => new Todo('3', 'change visa', true),
                '4' => new Todo('4', 'see joker', true),
                '5' => new Todo('5', 'interview', false),
            ];
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        return array_values($this->todos);
    }

    /**
     * {@inheritdoc}
     */
    public function findTodoById(string $id): Todo
    {
        if (!isset($this->todos[$id])) {
            throw new TodoNotFoundException();
        }

        return $this->todos[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, ?bool $done): Todo
    {
        $this->validateTodo($name);

        if (!isset($done)) {
            $done = false;
        }

        $nextId = uniqid();

        $todo = new Todo($nextId, $name, $done);

        $this->todos[$nextId] = $todo;

        return $todo;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, string $name, ?bool $done): Todo
    {
        $this->validateTodo($name);

        $todo = $this->findTodoById($id);

        if (!isset($done)) {
            $done = false;
        }

        $this->todos[$id] = new Todo($todo->getId(), $name, $done);

        return $this->todos[$id];
    }

    /**
     * @param string $name
     * @throws TodoInvalidNameException
     */
    private function validateTodo(string $name) {
        if (empty(trim($name))) {
            throw new TodoInvalidNameException();
        }
    }

    /**
     * @param string $id
     * @return array
     * @throws TodoNotFoundException
     */
    public function deleteTodoById(string $id):array
    {
        $this->findTodoById($id);

        unset($this->todos[$id]);
        return $this->findAll();
    }
}
