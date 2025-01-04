<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use React\EventLoop\Factory as LoopFactory;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$loop = LoopFactory::create();

require 'bot.php';

$loop->run();
