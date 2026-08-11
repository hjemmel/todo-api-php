<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Todo;

interface DatabaseInterface
{
    /**
     * Read the value at a database path.
     */
    public function getValue(string $path): mixed;

    /**
     * Whether a path exists in the database.
     */
    public function exists(string $path): bool;

    /**
     * Push a new child under a path and return the generated key.
     *
     * @param array<string, mixed> $value
     */
    public function push(string $path, array $value): string;

    /**
     * Update values at a path.
     *
     * @param array<string, mixed> $value
     */
    public function update(string $path, array $value): void;

    /**
     * Remove the value at a path.
     */
    public function remove(string $path): void;
}
