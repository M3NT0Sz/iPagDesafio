<?php
require __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
$channel = $connection->channel();
$queue = 'order_status_updates';
$channel->queue_declare($queue, false, true, false, false);

$callback = function ($msg) {
    echo "Mensagem recebida: ".$msg->body."\n";
};

$channel->basic_consume($queue, '', false, true, false, false, $callback);

echo "Aguardando mensagens...\n";

while (count($channel->callbacks)) {
    $channel->wait();
}
