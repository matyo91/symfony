<?php

declare(strict_types=1);

namespace Symfony\Component\Flow;

use Closure;
use Fiber;

/**
 * @template T1
 * @template T2
 *
 * @implements FlowInterface<T1>
 */
class Flow implements FlowInterface
{
    /**
     * @var array<Ip>
     */
    private array $ips = [];

    /**
     * @var array<Flow>
     */
    private array $flows = [];

    /**
     * @param Closure(T1): T2 $job
     */
    public function __construct(
        /** @var Closure(T1): T2 */
        private Closure $job,
    ) {}

    public function __invoke(Ip $ip): FlowInterface
    {
        $this->ips[] = $ip;

        return $this;
    }

    public function fn(FlowInterface $flow): FlowInterface
    {
        $this->flows[] = $flow;

        return $this;
    }

    public function await(): FlowInterface
    {
        $flows = array_merge([$this], $this->flows);

        $ips = $this->countIps();
        $fiberDatas = [];

        do {
            foreach ($flows as $index => $flow) {
                $nextIp = array_shift($flow->ips);
                if ($nextIp !== null) {
                    $fiber = new Fiber(static function () use ($flow, $nextIp) {
                        return $flow->job($nextIp->data);
                    });
                    $fiber->start();

                    $fiberDatas[] = [
                        'fiber' => $fiber,
                        'index' => $index,
                    ];
                }
            }

            foreach ($fiberDatas as $i => $fiberData) {
                if (!$fiberData['fiber']->isTerminated() and $fiberData['fiber']->isSuspended()) {
                    $fiberData['fiber']->resume();
                } else {
                    if (array_key_exists($fiberData['index'] + 1, $flows)) {
                        $data = $fiberData['fiber']->getReturn();
                        $flows[$fiberData['index'] + 1]->ips[] = new Ip($data);
                        $ips++;
                    }

                    $ips--;
                    unset($fiberDatas[$i]);
                }
            }
        } while ($ips > 0);

        return $this;
    }

    private function countIps(): int
    {
        return count($this->ips) + array_reduce($this->flows, static fn ($carry, FlowInterface $flow) => $carry + count($flow->ips), 0);
    }
}
