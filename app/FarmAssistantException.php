<?php

namespace App;

use RuntimeException;

class FarmAssistantException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
