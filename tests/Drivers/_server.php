<?php

// $ composer require react/http react/socket # install example using Composer
// $ php example.php # run example on command line, requires no additional web server

use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

require __DIR__ . '/../../vendor/autoload.php';

$http = new HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $response = [
        'method' => $request->getMethod(),
        'args' => $request->getQueryParams(),
        'json' => $request->getHeader('Content-Type') === ['application/json']
            ? json_decode($request->getBody())
            : []
    ];

    return Response::json($response);
});

$socket = new SocketServer('127.0.0.1:8888');

$http->listen($socket);
