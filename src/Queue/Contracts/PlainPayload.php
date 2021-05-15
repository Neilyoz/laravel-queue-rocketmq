<?php

namespace Neilyoz\LaravelQueueRocketMQ\Queue\Contracts;

interface PlainPayload
{
    /**
     * Get the plain payload of the job.
     *
     * @return string
     */
    public function getPayload(): string;
}
