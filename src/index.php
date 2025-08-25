<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$routes = require __DIR__ . '/routes.php';
$routes($app);

$app->run();
