<?php
declare(strict_types=1);

use App\Application\Actions\Todo\CreateTodoAction;
use App\Application\Actions\Todo\DeleteTodoAction;
use App\Application\Actions\Todo\ListTodoAction;
use App\Application\Actions\Todo\UpdateTodoAction;
use App\Application\Actions\Todo\ViewTodoAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });

    $app->add(function ($request, $handler) {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $response = $handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/todos', function (Group $group) {
        $group->get('', ListTodoAction::class);
        $group->get('/{id}', ViewTodoAction::class);
        $group->post('', CreateTodoAction::class);
        $group->put('/{id}', UpdateTodoAction::class);
        $group->delete('/{id}', DeleteTodoAction::class);
    });

    /*
     * Catch-all route to serve a 404 Not Found page if none of the routes match
     * NOTE: make sure this route is defined last
     */
        $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
            throw new HttpNotFoundException($request);
        });
};
