<?php

use React\Http\HttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

$server = new HttpServer(function (ServerRequestInterface $request) {
    return new Response(200, ['Content-Type' => 'text/plain'], "OK");
});

$socket = new React\Socket\SocketServer('0.0.0.0:8080', [], $loop);
$server->listen($socket);