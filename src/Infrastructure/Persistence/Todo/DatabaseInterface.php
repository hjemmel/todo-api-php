<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Todo;

use Kreait\Firebase\Database\Reference;

interface DatabaseInterface
{
    /**
     * Get a reference to a specific path in the database
     *
     * @param string $path
     * @return Reference
     */
    public function getReference(string $path): Reference;
}
