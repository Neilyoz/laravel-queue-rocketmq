# Laravel 8 RocketMQ 组件

fork on freyo/laravel-queue-rocketmq

thanks freyo！！！

Add env value to .env

```env
QUEUE_CONNECTION=rocketmq

ROCKETMQ_ACCESS_ID=
ROCKETMQ_ACCESS_KEY=
ROCKETMQ_ENDPOINT=
ROCKETMQ_INSTANCE_ID=
ROCKETMQ_GROUP_ID=
ROCKETMQ_QUEUE=
ROCKETMQ_USE_MESSAGE_TAG=false
ROCKETMQ_WAIT_SECONDS=0
ROCKETMQ_PLAIN_ENABLE=false
ROCKETMQ_PLAIN_JOB=
```

```php
return [
    'providers' => [
        ...
        Neilyoz\LaravelQueueRocketMQ\LaravelQueueRocketMQServiceProvider::class,
    ],
];
```
