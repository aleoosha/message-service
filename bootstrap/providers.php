<?php

use App\Providers\AppServiceProvider;
use Mateusjunges\Kafka\Providers\LaravelKafkaServiceProvider;

return [
    AppServiceProvider::class,
    LaravelKafkaServiceProvider::class,
];
