<?php

namespace App\Controllers;

use PhpAmqpLib\Message\AMQPMessage;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrderController
{
    public function createOrder(Request $request, Response $response, $args)
    {
        $pdo = require __DIR__ . '/../Utils/db.php';
        // Garante leitura correta do JSON
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);
        if (!$data) {
            $data = $request->getParsedBody();
        }

        // Cliente
        $customer = $data['customer'] ?? [];
        if (empty($customer['id'])) {
            // Novo cliente
            $stmt = $pdo->prepare('INSERT INTO customers (name, document, email, phone) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $customer['name'] ?? '',
                $customer['document'] ?? '',
                $customer['email'] ?? '',
                $customer['phone'] ?? null
            ]);
            $customerId = $pdo->lastInsertId();
        } else {
            $customerId = $customer['id'];
        }

        // Pedido
        $order = $data['order'] ?? [];
        $orderNumber = 'ORD-' . strtoupper(uniqid());
        $stmt = $pdo->prepare('INSERT INTO orders (customer_id, order_number, total_value, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $customerId,
            $orderNumber,
            $order['total_value'] ?? 0,
            'PENDING'
        ]);
        $orderId = $pdo->lastInsertId();

        // Itens do pedido
        $items = $order['items'] ?? [];
        foreach ($items as $item) {
            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_name, quantity, unit_value) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $orderId,
                $item['product_name'] ?? '',
                $item['quantity'] ?? 1,
                $item['unit_value'] ?? 0
            ]);
        }

        $result = [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'status' => 'PENDING',
            'total_value' => $order['total_value'] ?? 0,
            'customer' => [
                'id' => $customerId,
                'name' => $customer['name'] ?? '',
                'document' => $customer['document'] ?? '',
                'email' => $customer['email'] ?? '',
                'phone' => $customer['phone'] ?? ''
            ],
            'items' => $items,
            'created_at' => date('c')
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getOrder(Request $request, Response $response, $args)
    {
        $pdo = require __DIR__ . '/../Utils/db.php';
        $orderId = $args['order_id'] ?? null;
        if (!$orderId) {
            $response->getBody()->write(json_encode(['error' => 'order_id é obrigatório']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Busca pedido
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            $response->getBody()->write(json_encode(['error' => 'Pedido não encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Busca cliente
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
        $stmt->execute([$order['customer_id']]);
        $customer = $stmt->fetch();

        // Busca itens
        $stmt = $pdo->prepare('SELECT product_name, quantity, unit_value FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();

        $result = [
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'total_value' => $order['total_value'],
            'customer' => [
                'id' => $customer['id'] ?? null,
                'name' => $customer['name'] ?? '',
                'document' => $customer['document'] ?? '',
                'email' => $customer['email'] ?? '',
                'phone' => $customer['phone'] ?? ''
            ],
            'items' => $items,
            'created_at' => $order['created_at']
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updateStatus(Request $request, Response $response, $args)
    {
    $pdo = require __DIR__ . '/../Utils/db.php';
    require_once __DIR__ . '/../Utils/rabbitmq.php';
        $orderId = $args['order_id'] ?? null;
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);
        if (!$data) {
            $data = $request->getParsedBody();
        }
        $newStatus = $data['status'] ?? null;
        $notes = $data['notes'] ?? null;
        if (!$orderId || !$newStatus) {
            $response->getBody()->write(json_encode(['error' => 'order_id e status são obrigatórios']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Buscar pedido atual
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            $response->getBody()->write(json_encode(['error' => 'Pedido não encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $oldStatus = $order['status'];

        // Atualizar status
        $stmt = $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$newStatus, $orderId]);

        // Publicar mensagem no RabbitMQ
        try {
            $connection = getRabbitConnection();
            $channel = $connection->channel();
            $queue = 'order_status_updates';
            $channel->queue_declare($queue, false, true, false, false);
            $msgData = [
                'order_id' => $orderId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes,
                'timestamp' => date('c'),
                'user_id' => 'system'
            ];
            $msg = new AMQPMessage(json_encode($msgData), ['content_type' => 'application/json']);
            $channel->basic_publish($msg, '', $queue);
            $channel->close();
            $connection->close();
        } catch (\Throwable $e) {
            // Apenas loga erro, mas não impede fluxo
            error_log('Erro ao publicar no RabbitMQ: ' . $e->getMessage());
        }

        // Registrar log de notificação
        $stmt = $pdo->prepare('INSERT INTO notification_logs (order_id, old_status, new_status, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$orderId, $oldStatus, $newStatus, $notes]);

        $response->getBody()->write(json_encode([
            'order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'message' => 'Status atualizado com sucesso'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function listOrders(Request $request, Response $response, $args)
    {
        $pdo = require __DIR__ . '/../Utils/db.php';
        // Permitir filtros simples via query string (ex: status, customer_id)
        $params = $request->getQueryParams();
        $where = [];
        $values = [];
        if (!empty($params['status'])) {
            $where[] = 'o.status = ?';
            $values[] = $params['status'];
        }
        if (!empty($params['customer_id'])) {
            $where[] = 'o.customer_id = ?';
            $values[] = $params['customer_id'];
        }
        $sql = 'SELECT o.*, c.name as customer_name, c.document as customer_document, c.email as customer_email, c.phone as customer_phone FROM orders o JOIN customers c ON o.customer_id = c.id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY o.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $orders = $stmt->fetchAll();

        // Buscar itens de todos os pedidos
        $orderIds = array_column($orders, 'id');
        $itemsByOrder = [];
        if ($orderIds) {
            $in = implode(',', array_fill(0, count($orderIds), '?'));
            $stmt = $pdo->prepare('SELECT order_id, product_name, quantity, unit_value FROM order_items WHERE order_id IN (' . $in . ')');
            $stmt->execute($orderIds);
            foreach ($stmt->fetchAll() as $item) {
                $itemsByOrder[$item['order_id']][] = [
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_value' => $item['unit_value']
                ];
            }
        }

        $result = [];
        foreach ($orders as $order) {
            $result[] = [
                'order_id' => $order['id'],
                'order_number' => $order['order_number'],
                'status' => $order['status'],
                'total_value' => $order['total_value'],
                'customer' => [
                    'id' => $order['customer_id'],
                    'name' => $order['customer_name'],
                    'document' => $order['customer_document'],
                    'email' => $order['customer_email'],
                    'phone' => $order['customer_phone']
                ],
                'items' => $itemsByOrder[$order['id']] ?? [],
                'created_at' => $order['created_at'],
                'updated_at' => $order['updated_at']
            ];
        }
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getSummary(Request $request, Response $response, $args)
    {
        $pdo = require __DIR__ . '/../Utils/db.php';
        // Total de pedidos
        $total = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        // Total por status
        $statusStmt = $pdo->query('SELECT status, COUNT(*) as count FROM orders GROUP BY status');
        $statusCounts = [];
        foreach ($statusStmt->fetchAll() as $row) {
            $statusCounts[$row['status']] = (int)$row['count'];
        }
        // Valor total dos pedidos
        $totalValue = $pdo->query('SELECT SUM(total_value) FROM orders')->fetchColumn();
        // Valor total por status
        $valueByStatusStmt = $pdo->query('SELECT status, SUM(total_value) as total FROM orders GROUP BY status');
        $valueByStatus = [];
        foreach ($valueByStatusStmt->fetchAll() as $row) {
            $valueByStatus[$row['status']] = (float)$row['total'];
        }
        $result = [
            'total_orders' => (int)$total,
            'orders_by_status' => $statusCounts,
            'total_value' => (float)$totalValue,
            'total_value_by_status' => $valueByStatus
        ];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
