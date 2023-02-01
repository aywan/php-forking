<?php

declare(strict_types=1);

namespace App;

final class Packet
{
    public const PING = 1;
    public const PONG = 2;
    public const TASK = 3;
    public const RESULT = 4;
    public const READY = 5;
    public const CLOSE = 255;

    public function __construct(
        public readonly int $type,
        public readonly ?array $payload = null
    )
    {
    }

    public static function ready(): self
    {
        return new self(self::READY);
    }

    public static function ping(?array $payload): self
    {
        return new self(self::PONG, $payload);
    }

    public static function pong(?array $payload): self
    {
        return new self(self::PONG, $payload);
    }

    public static function result(array $payload): self
    {
        return new self(self::RESULT, $payload);
    }

    public static function task(array $payload): self
    {
        return new self(self::TASK, $payload);
    }

    public static function close(): self
    {
        return new self(self::CLOSE);
    }
}