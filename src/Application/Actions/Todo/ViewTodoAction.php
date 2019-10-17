<?php
declare(strict_types=1);

namespace App\Application\Actions\Todo;

use Psr\Http\Message\ResponseInterface as Response;

class ViewTodoAction extends TodoAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $todoId = (string) $this->resolveArg('id');
        $todo = $this->todoRepository->findTodoById($todoId);

        $this->logger->info("Todo of id `${todoId}` was viewed.");

        return $this->respondWithData($todo);
    }
}
