<?php
declare(strict_types=1);

namespace App\Domain\Todo;

use App\Domain\DomainException\DomainRecordNotFoundException;

class TodoNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The todo you requested does not exist.';
}
