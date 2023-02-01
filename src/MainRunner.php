<?php

declare(strict_types=1);

namespace App;

use Psr\Log\LoggerInterface;

class MainRunner
{
    /**
     * @var Child[]
     */
    private array $child = [];
    private MainState $state = MainState::INIT;

    private ?LoggerInterface $log;

    public function __construct(LoggerInterface $log = null)
    {
        $this->log = $log;
    }

    public function run(\Generator $taskGen, int $forkCount = 3): int
    {
        for ($i = 0; $i < $forkCount; $i++) {
            $child = $this->forking($i);
            if (null !== $child) {
                return $child->run();
            }
        }

        $this->state = MainState::CYCLE;

        $this->mainCycle($taskGen);


        return 0;
    }

    private function forking(int $childId): ?ChildRunner
    {
        if (socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $socksPair) === false) {
            throw new \Exception('fail create socket pair');
        }

        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new \Exception('fail fork');
        }

        // parent
        if ($pid > 0) {
            socket_close($socksPair[0]);
            $this->child[$pid] = new Child($pid, new Socket($socksPair[1]));
            return null;
        }

        // child
        socket_close($socksPair[1]);

        return new ChildRunner(new Socket($socksPair[0], true), $this->log);
    }

    /**
     * @return mixed
     */
    public function mainCycle(\Generator $taskGen): int
    {
        while (true) {
            $child = $this->waitReadyChild();
            if (null === $child) {
                return -1;
            }

            $task = $taskGen->current();
            $child->sendTask($task);

            $taskGen->next();
            if (! $taskGen->valid()) {
                break;
            }
        }

        $this->state = MainState::CLOSING;

        while (null !== $child = $this->waitReadyChild()) {
            $child->stopWork();
        }

        $this->state = MainState::EXIT;

        return 0;
    }

    private function waitReadyChild(): ?Child
    {
        while (true) {
            foreach ($this->child as $c) {
                if ($c->isNotStopped() && $c->isReady()) {
                    return $c;
                }
            }

            if (! $this->waitChildRead()) {
                break;
            }
            $this->processResults();
        }
        $this->processResults();

        return null;
    }

    /**
     * @return MainState
     */
    public function getState(): MainState
    {
        return $this->state;
    }

    private function waitChildRead(): bool
    {
        $read = [];
        foreach ($this->child as $c) {
            if ($c->isNotStopped()) {
                $read[$c->getPid()] = $c->getSocket()->getResource();
            }
        }

        if (empty($read)) {
            return false;
        }

        $w = [];
        $e = [];
        $change = socket_select($read, $w, $e, 0, 100);

        if (false === $change) {
            throw new \Exception('Select fail');
        }

        if ($change > 0) {
            foreach (array_keys($read) as $id) {
                $this->child[$id]->readState();
            }
        }

        return true;
    }

    /**
     * @return void
     */
    public function processResults(): void
    {
        foreach ($this->child as $c) {
            if ($c->isResult()) {
                $packet = $c->getCurPacket();
                $this->log && $this->log->info("child " . $c->getPid() . " resulted", $packet?->payload);
                $c->setResultProcessed();
            }
        }
    }
}