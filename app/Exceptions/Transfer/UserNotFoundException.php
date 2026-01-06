<?php

declare(strict_types=1);

namespace App\Exceptions\Transfer;

use Exception;

class UserNotFoundException extends Exception
{
    public function __construct(string $field)
    {
        parent::__construct("User not found: {$field}");
    }
}
