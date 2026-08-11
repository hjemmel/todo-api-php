<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Todo;

use Kreait\Firebase\Contract\Database;

class DatabaseWrapper implements DatabaseInterface
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function getValue(string $path): mixed
    {
        return $this->database->getReference($path)->getValue();
    }

    public function exists(string $path): bool
    {
        return $this->database->getReference($path)->getSnapshot()->exists();
    }

    public function push(string $path, array $value): string
    {
        $reference = $this->database->getReference($path)->push($value);
        $key = $reference->getKey();

        if ($key === null) {
            throw new \RuntimeException(sprintf('Failed to push value at path "%s"', $path));
        }

        return $key;
    }

    public function update(string $path, array $value): void
    {
        $this->database->getReference($path)->update($value);
    }

    public function remove(string $path): void
    {
        $this->database->getReference($path)->remove();
    }
}
