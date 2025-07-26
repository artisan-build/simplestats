<?php

namespace App\Exceptions;

use Exception;

class ImportCanceledException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Import canceled', $code, $previous);
    }
}
