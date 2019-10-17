<?php
declare(strict_types=1);

namespace App\Application\Actions\Todo;

use Psr\Http\Message\ResponseInterface as Response;

class CreateTodoAction extends TodoAction
{

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $body = $this->request->getParsedBody();
        $todo = $this->todoRepository->create($body["name"], $body["done"]);

        $this->logger->info("Todo has been created");

        return $this->respondWithData($todo);
    }
}