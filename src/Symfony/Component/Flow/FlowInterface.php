<?php

declare(strict_types=1);

namespace Symfony\Component\Flow;

/**
 * @template T1
 */
interface FlowInterface
{
    /**
     * @param Ip<T1> $ip
     */
    public function __invoke(Ip $ip): self;

    /**
     * @template T2
     *
     * @param FlowInterface<T2> $flow
     * @return FlowInterface<T1>
     */
    public function fn(self $flow): self;

    /**
     * Await asynchonous call for current IPs.
     * After await, all IPs have been proceed, it continues synchronously.
     */
    public function await(): self;
}
