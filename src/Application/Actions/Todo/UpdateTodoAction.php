<?php
declare(strict_types=1);

namespace App\Application\Actions\Todo;

use Psr\Http\Message\ResponseInterface as Response;

class UpdateTodoAction extends TodoAction
{

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $todoId = (string)$this->resolveArg('id');
        $body = $this->request->getParsedBody();
        $todo = $this->todoRepository->update($todoId, $body["name"], (bool)$body["done"]);

        $this->logger->info("Todo has been updated");

        return $this->respondWithData($todo);
    }
}