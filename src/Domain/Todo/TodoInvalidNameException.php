<?php
declare(strict_types=1);

namespace App\Domain\Todo;

use App\Domain\DomainException\DomainRecordNotFoundException;

class TodoInvalidNameException extends DomainRecordNotFoundException
{
    public $message = 'Name is empty.';
}
