<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(200);
    echo "OK";
    exit;
}

require 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

require 'bot.php';
