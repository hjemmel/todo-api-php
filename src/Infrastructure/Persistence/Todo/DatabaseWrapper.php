<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Todo;

use Kreait\Firebase\Database;
use Kreait\Firebase\Database\Reference;

class DatabaseWrapper implements DatabaseInterface
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Get a reference to a specific path in the database
     *
     * @param string $path
     * @return Reference
     */
    public function getReference(string $path): Reference
    {
        return $this->database->getReference($path);
    }
}
