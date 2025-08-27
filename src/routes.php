<?php
use Slim\App;
use App\Controllers\OrderController;

return function (App $app) {
    $app->post('/orders', [OrderController::class, 'createOrder']);
    $app->get('/orders/summary', [OrderController::class, 'getSummary']);
    $app->get('/orders/{order_id}', [OrderController::class, 'getOrder']);
    $app->put('/orders/{order_id}/status', [OrderController::class, 'updateStatus']);
    $app->get('/orders', [OrderController::class, 'listOrders']);

    // Health check endpoint
    $app->get('/health', function ($request, $response) {
        $response->getBody()->write(json_encode(['status' => 'ok']));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
