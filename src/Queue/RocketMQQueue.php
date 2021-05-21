<?php

namespace Neilyoz\LaravelQueueRocketMQ\Queue;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Support\Arr;
use MQ\Exception\MessageNotExistException;
use MQ\Model\TopicMessage;
use MQ\MQClient;
use MQ\MQConsumer;
use MQ\MQProducer;
use Neilyoz\LaravelQueueRocketMQ\Queue\Jobs\RocketMQJob;
use ReflectionException;
use ReflectionMethod;

class RocketMQQueue extends Queue implements QueueContract
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var ReflectionMethod
     */
    private $createPayload;

    /**
     * @var MQClient
     */
    protected $client;

    /**
     * RocketMQQueue constructor.
     * @param MQClient $client
     * @param array $config
     * @throws ReflectionException
     */
    public function __construct(MQClient $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;

        $this->createPayload = new ReflectionMethod($this, 'createPayload');
    }

    public function isPlain(): bool
    {
        return (bool)Arr::get($this->config, 'plain.enable');
    }

    public function getPlainJob(): string
    {
        return Arr::get($this->config, 'plain.job');
    }

    public function size($queue = null): int
    {
        return 1;
    }

    public function push($job, $data = '', $queue = null)
    {
        if ($this->isPlain()) {
            return $this->pushRaw($job->getPayload(), $queue);
        }

        $payload = $this->createPayload->getNumberOfParameters() === 3
            ? $this->createPayload($job, $queue, $data) // version >= 5.7
            : $this->createPayload($job, $data);

        return $this->pushRaw($payload, $queue);
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $message = new TopicMessage($payload);

        if ($this->config['use_message_tag'] && $queue) {
            $message->setMessageTag($queue);
        }

        if ($delay = Arr::get($options, 'delay', 0)) {
            $message->setStartDeliverTime(time() * 1000 + $delay * 1000);
        }

        return $this->getProducer(
            $this->config['use_message_tag'] ? $this->config['queue'] : $queue
        )->publishMessage($message);
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        $delay = method_exists($this, 'getSeconds')
            ? $this->getSeconds($delay)
            : $this->secondsUntil($delay);

        if ($this->isPlain()) {
            return $this->pushRaw($job->getPayload(), $queue, ['delay' => $delay]);
        }

        $payload = $this->createPayload->getNumberOfParameters() === 3
            ? $this->createPayload($job, $queue, $data) // version >= 5.7
            : $this->createPayload($job, $data);

        return $this->pushRaw($payload, $queue, ['delay' => $delay]);
    }

    /**
     * @throws Exception
     */
    public function pop($queue = null): ?RocketMQJob
    {
        try {
            $consumer = $this->config['use_message_tag']
                ? $this->getConsumer($this->config['queue'], $queue)
                : $this->getConsumer($queue);

            /** @var array $messages */
            $messages = $consumer->consumeMessage(1, $this->config['wait_seconds']);
        } catch (Exception $e) {
            if ($e instanceof MessageNotExistException) {
                return null;
            }

            throw $e;
        }

        return new RocketMQJob(
            $this->container ?: Container::getInstance(),
            $this,
            Arr::first($messages),
            $this->config['use_message_tag'] ? $this->config['queue'] : $queue,
            $this->connectionName ?? null
        );
    }

    /**
     * @param string|null $topicName
     * @return MQProducer
     */
    public function getProducer(?string $topicName): MQProducer
    {
        return $this->client->getProducer(
            $this->config['instance_id'],
            $topicName ?: $this->config['queue']
        );
    }

    /**
     * @param string|null $topicName
     * @param string|null $messageTag
     * @return MQConsumer
     */
    public function getConsumer(?string $topicName = null, ?string $messageTag = null): MQConsumer
    {
        return $this->client->getConsumer(
            $this->config['instance_id'],
            $topicName ?: $this->config['queue'],
            $this->config['group_id'],
            $messageTag
        );
    }
}
