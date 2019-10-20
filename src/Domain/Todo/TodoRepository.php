<?php
declare(strict_types=1);

namespace App\Domain\Todo;

interface TodoRepository
{
    /**
     * @return Todo[]
     */
    public function findAll(): array;

    /**
     * @param string $id
     * @return Todo
     * @throws TodoNotFoundException
     */
    public function findTodoById(string $id): Todo;

    /**
     * @param string $id
     * @return array
     * @throws TodoNotFoundException
     */
    public function deleteTodoById(string $id):array;

    /**
     * @param string $name
     * @param bool $done
     * @return Todo
     * @throws TodoInvalidNameException
     */
    public function create(string $name, ?bool $done): Todo;

    /**
     * @param string $id
     * @param string $name
     * @param bool $done
     * @return Todo
     * @throws TodoInvalidNameException
     * @throws TodoNotFoundException
     */
    public function update(string $id, string $name, ?bool $done): Todo;
}
