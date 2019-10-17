<?php
declare(strict_types=1);

namespace App\Application\Actions\Todo;

use Psr\Http\Message\ResponseInterface as Response;

class DeleteTodoAction extends TodoAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $todoId = (string) $this->resolveArg('id');
        $todos = $this->todoRepository->deleteTodoById($todoId);

        $this->logger->info("Todo of id `${todoId}` was deleted.");

        return $this->respondWithData($todos);
    }
}
