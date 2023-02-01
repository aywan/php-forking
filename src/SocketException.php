<?php

declare(strict_types=1);

namespace App;

class SocketException extends \Exception
{
    public function __construct(string $message, $socket, ?\Throwable $previous = null)
    {
        $err = socket_last_error($socket);
        $des = socket_strerror($err);

        parent::__construct($message . ': ' . $des, $err, $previous);
    }
}