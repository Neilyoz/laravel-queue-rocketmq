<?php

namespace Neilyoz\LaravelQueueRocketMQ;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use Neilyoz\LaravelQueueRocketMQ\Queue\Connectors\RocketMQConnector;

class LaravelQueueRocketMQServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // mergeConfig to queue
        $this->mergeConfigFrom(__DIR__ . '/../config/rocketmq.php', 'queue.connections.rocketmq');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * @var QueueManager $queue
         */
        $queue = $this->app['queue'];

        $queue->addConnector('rocketmq', function () {
            return new RocketMQConnector();
        });
    }
}
