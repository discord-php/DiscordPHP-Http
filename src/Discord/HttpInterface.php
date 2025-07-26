<?php

/*
 * This file is a part of the DiscordPHP-Http project.
 *
 * Copyright (c) 2021-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

namespace Discord\Http;

use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

/**
 * Discord HTTP client.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
interface HttpInterface
{
    public function setDriver(DriverInterface $driver): void;
    public function get($url, $content = null, array $headers = []): PromiseInterface;
    public function post($url, $content = null, array $headers = []): PromiseInterface;
    public function put($url, $content = null, array $headers = []): PromiseInterface;
    public function patch($url, $content = null, array $headers = []): PromiseInterface;
    public function delete($url, $content = null, array $headers = []): PromiseInterface;
    public function queueRequest(string $method, Endpoint $url, $content, array $headers = []): PromiseInterface;
    public static function isInteractionEndpoint(Request $request): bool;
    public function handleError(ResponseInterface $response): \Throwable;
    public function getUserAgent(): string;
}