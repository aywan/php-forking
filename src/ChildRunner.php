<?php

declare(strict_types=1);

namespace App;

use Psr\Log\LoggerInterface;

class ChildRunner
{
    private Socket $socket;

    private ChildState $state = ChildState::INIT;
    private ?LoggerInterface $log;

    public function __construct(Socket $socket, ?LoggerInterface $log = null)
    {
        $this->socket = $socket;
        $this->log = $log;
    }

    public function run(): int
    {
        $this->socket->writePacket(Packet::ready());
        $this->state = ChildState::READY;

        while (true) {
            $this->socket->waitForRead();
            $packet = $this->socket->readPacket();
            if (null === $packet) {
                usleep(100);
                continue;
            }

            switch ($packet->type) {
                case Packet::CLOSE:
                    return 0;

                case Packet::PING:
                    $this->socket->writePacket(Packet::pong($packet->payload));
                    break;

                case Packet::TASK:
                    $this->state = ChildState::WORKING;
                    $res = $this->execTask($packet->payload);
                    $this->state = ChildState::SEND_RESULT;
                    $this->socket->writePacket(Packet::result($res));
                    $this->state = ChildState::READY;
                    break;
            }
        }

        return 0;
    }

    private function execTask(?array $payload): array
    {
        $id = $payload['id'] ?? '';
        $this->log && $this->log->info("exec task: $id", $payload);

        switch ($id) {
            case 'sum/sleep':
                sleep($payload['sleep']);
                return ['res' => $payload['a'] + $payload['b']];
        }

        return [];
    }
}