<?php

namespace App\Exceptions;

use RuntimeException;

class NativeOAuthVerificationException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus = 422,
        string $message = 'The OAuth credential could not be verified.'
    ) {
        parent::__construct($message);
    }
}
