<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();


// Middleware de rate limiting (10 req/min por IP)
use App\Middleware\RateLimitMiddleware;
$app->add(new RateLimitMiddleware(10, 60));

$routes = require __DIR__ . '/routes.php';
$routes($app);

$app->run();
