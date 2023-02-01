<?php

declare(strict_types=1);

namespace App;

class Socket
{
    /** @var resource */
    private $socket;

    public function __construct($socket, bool $isBlock = false)
    {
        if (!$isBlock) {
            socket_set_nonblock($socket);
        }
        $this->socket = $socket;
    }

    public function __destruct()
    {
        if ($this->socket) {
            socket_close($this->socket);
        }
    }

    public function writePacket(Packet $packet): int
    {
        $payload = $packet->payload === null ? '' : json_encode($packet->payload ?? '');
        $len = strlen($payload);
        $msg = pack('vV', $packet->type, $len) . $payload;

        $res = socket_write($this->socket, $msg, strlen($msg));
        if (false === $res) {
            throw new SocketException('write error', $this->socket);
        }
        return $res;
    }

    public function waitForRead(): void
    {
        $r = [$this->socket];
        $w = [$this->socket];
        $e = [$this->socket];

        $res = socket_select($r, $w, $e, 0);
        if($res === false) {
            throw new SocketException("socket wait err", $this->socket);
        }
    }

    public function readPacket(): ?Packet
    {
        $header = socket_read($this->socket, 6);
        if ($header === false) {
            throw new SocketException('read error', $this->socket);
        }
        if ($header === '') {
            return null;
        }

        $h = unpack('vtype/Vlen', $header);
        if ($h['len'] > 0) {
            $data = socket_read($this->socket, $h['len']);
            $payload = json_decode($data, true);
        } else {
            $payload = null;
        }

        return new Packet($h['type'], $payload);
    }

    public function getResource()
    {
        return $this->socket;
    }
}