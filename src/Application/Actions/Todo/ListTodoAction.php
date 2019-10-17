<?php
declare(strict_types=1);

namespace App\Application\Actions\Todo;

use Psr\Http\Message\ResponseInterface as Response;

class ListTodoAction extends TodoAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $todos = $this->todoRepository->findAll();

        $this->logger->info("Todo list was viewed.");

        return $this->respondWithData($todos);
    }
}
