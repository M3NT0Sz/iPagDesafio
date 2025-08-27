<?php
// Worker RabbitMQ: Consome mensagens da fila order_status_updates e registra log/validações

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Utils/rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;

$pdo = require __DIR__ . '/src/Utils/db.php';

$connection = getRabbitConnection();
$channel = $connection->channel();
$queue = 'order_status_updates';
$channel->queue_declare($queue, false, true, false, false);

echo " [*] Aguardando mensagens na fila '{$queue}'. Para sair pressione CTRL+C\n";

$callback = function (AMQPMessage $msg) use ($pdo) {
    $data = json_decode($msg->getBody(), true);
    if (!$data) {
        echo "[ERROR] Mensagem inválida recebida: não é JSON\n";
        return;
    }
    // Validação dos campos obrigatórios
    $required = ['order_id', 'old_status', 'new_status', 'timestamp', 'user_id'];
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        echo "[ERROR] Payload faltando campos obrigatórios: " . implode(', ', $missing) . "\n";
        return;
    }
    $orderId = $data['order_id'];
    $oldStatus = $data['old_status'];
    $newStatus = $data['new_status'];
    $timestamp = $data['timestamp'];
    $userId = $data['user_id'];
    $notes = $data['notes'] ?? null;

    // Log estruturado no console
    echo "[{$timestamp}] INFO: Order {$orderId} status changed from {$oldStatus} to {$newStatus}\n";
    echo "[{$timestamp}] INFO: Notification sent by user {$userId}" . ($notes ? ": {$notes}" : "") . "\n";

    // Registra log no banco
    $stmt = $pdo->prepare('INSERT INTO notification_logs (order_id, old_status, new_status, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([$orderId, $oldStatus, $newStatus, $notes]);
};

$channel->basic_consume($queue, '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}
