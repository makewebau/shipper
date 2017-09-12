<?php

namespace MakeWeb\Shipper\Exceptions;

use Exception;

class FileAlreadyExistsException extends Exception
{
    public function __construct($path)
    {
        parent::__construct($path.' already exists.');
    }
}
