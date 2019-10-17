<?php
declare(strict_types=1);

use App\Domain\Todo\TodoRepository;
use App\Infrastructure\Persistence\Todo\FirebaseTodoRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    // Here we map our TodoRepository interface to its in memory implementation
    $containerBuilder->addDefinitions([
        TodoRepository::class => \DI\autowire(FirebaseTodoRepository::class),
    ]);
};
