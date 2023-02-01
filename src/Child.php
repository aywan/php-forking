<?php

declare(strict_types=1);

namespace App;

class Child
{
    private readonly int $pid;
    private readonly Socket $socket;

    private ChildState $status = ChildState::INIT;

    private ?Packet $curPacket = null;

    public function __construct(int $pid, Socket $socket)
    {
        $this->socket = $socket;
        $this->pid = $pid;
    }

    public function isReady(): bool
    {
        return $this->status === ChildState::READY;
    }

    public function isResult(): bool
    {
        return $this->status === ChildState::SEND_RESULT;
    }

    public function isNotStopped(): bool
    {
        return $this->status !== ChildState::EXIT;
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @return Socket
     */
    public function getSocket(): Socket
    {
        return $this->socket;
    }

    public function readState(): void
    {
        $this->curPacket = $this->socket->readPacket();
        if (! $this->curPacket) {
            return;
        }


        switch ($this->curPacket->type) {
            case Packet::READY:
                $this->status = ChildState::READY;
                break;

            case Packet::PONG:
            case Packet::RESULT:
                $this->status = ChildState::SEND_RESULT;
                break;
        }
    }

    /**
     * @return Packet|null
     */
    public function getCurPacket(): ?Packet
    {
        return $this->curPacket;
    }

    public function sendTask(array $task): void
    {
        $this->socket->writePacket(Packet::task($task));
        $this->status = ChildState::WORKING;
    }

    public function stopWork(): void
    {
        $this->socket->writePacket(Packet::close());
        $this->status = ChildState::EXIT;
    }

    public function setResultProcessed(): void
    {
        $this->curPacket = null;
        $this->status = ChildState::READY;
    }
}