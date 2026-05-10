<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Router;

define('BASE_PATH', dirname(__DIR__));

$router = new Router();
$router->dispatch();
