<?php
// Teste automatizado básico para o endpoint POST /orders
use PHPUnit\Framework\TestCase;

class OrdersApiTest extends TestCase
{
    public function testCreateOrder()
    {
        $payload = [
            'customer' => [
                'id' => 1,
                'name' => 'Fulano de Tal',
                'document' => '12345678900',
                'email' => 'fulano@email.com',
                'phone' => '11999999999'
            ],
            'order' => [
                'total_value' => 150.00,
                'items' => [
                    [
                        'product_name' => 'Produto 1',
                        'quantity' => 2,
                        'unit_value' => 50.00
                    ],
                    [
                        'product_name' => 'Produto 2',
                        'quantity' => 1,
                        'unit_value' => 50.00
                    ]
                ]
            ]
        ];

        $ch = curl_init('http://localhost:8080/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(201, $httpCode, 'Deve retornar HTTP 201 Created');
        $data = json_decode($response, true);
        $this->assertArrayHasKey('order_id', $data, 'Deve retornar order_id no corpo da resposta');
    }
}
