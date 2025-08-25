<?php
// Conexão PDO com MySQL
$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'ipag';
$user = getenv('DB_USER') ?: 'ipaguser';
$pass = getenv('DB_PASS') ?: 'ipagpass';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new RuntimeException('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}
return $pdo;
