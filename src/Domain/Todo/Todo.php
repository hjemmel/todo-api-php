<?php

declare(strict_types=1);

namespace App\Domain\Todo;

use JsonSerializable;

class Todo implements JsonSerializable
{
    /**
     * @var string|null
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $done;

    /**
     * @param string|null  $id
     * @param string    $name
     * @param bool      $done
     */
    public function __construct(?string $id, string $name, bool $done)
    {
        $this->id = $id;
        $this->name = trim($name);
        $this->done = $done;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isDone(): bool
    {
        return $this->done;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'done' => $this->done
        ];
    }
}
