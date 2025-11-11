<?php

namespace App\Exceptions;

use Exception;

class ShopifyApiException extends Exception
{
    public function __construct(
        string $message,
        public ?array $errors = null,
        public int $status = 500
    ) {
        parent::__construct($message, $status);
    }
}
