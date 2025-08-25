<?php
// Conexão básica com RabbitMQ usando php-amqplib

use PhpAmqpLib\Connection\AMQPStreamConnection;

function getRabbitConnection() {
    $host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
    $port = getenv('RABBITMQ_PORT') ?: 5672;
    $user = getenv('RABBITMQ_USER') ?: 'guest';
    $pass = getenv('RABBITMQ_PASS') ?: 'guest';
    return new AMQPStreamConnection($host, $port, $user, $pass);
}
