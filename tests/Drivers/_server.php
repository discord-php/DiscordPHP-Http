<?php

/*
 * This file is a part of the DiscordPHP-Http project.
 *
 * Copyright (c) 2021-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

require __DIR__.'/../../vendor/autoload.php';

$http = new HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $response = [
        'method' => $request->getMethod(),
        'args' => $request->getQueryParams(),
        'json' => $request->getHeader('Content-Type') === ['application/json']
            ? json_decode($request->getBody())
            : [],
    ];

    return Response::json($response);
});

$socket = new SocketServer('127.0.0.1:8888');

$http->listen($socket);
