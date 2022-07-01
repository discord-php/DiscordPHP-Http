<?php

/*
 * This file is a part of the DiscordPHP-Http project.
 *
 * Copyright (c) 2021-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

namespace Discord\Http\Drivers;

use Discord\Http\DriverInterface;
use Discord\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\TaskQueueInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

/**
 * guzzlehttp/guzzle driver for Discord HTTP client. (still with React Event Loop/Promise)
 *
 * @author SQKo
 */
class Guzzle implements DriverInterface
{
    /**
     * ReactPHP event loop.
     *
     * @var LoopInterface
     */
    protected $loop;

    /**
     * GuzzleHTTP/Guzzle client.
     *
     * @var Client
     */
    protected $client;

    /**
     * GuzzleHTTP/Promise global queue.
     *
     * @var TaskQueueInterface
     */
    protected $queue;

    /**
     * A single mark set on the run.
     *
     * @var bool
     */
    protected $isRun = false;

    /**
     * Constructs the Guzzle driver.
     *
     * @param array $options
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;

        // Allow 400 and 500 HTTP requests to be resolved rather than rejected.
        $options['http_errors'] = false;
        $this->client = new Client($options);

        $this->queue = Utils::queue();
    }

    public function runRequest(Request $request): ExtendedPromiseInterface
    {
        // Create a React promise
        $deferred = new Deferred();
        $promise = $deferred->promise();

        $this->client->requestAsync($request->getMethod(), $request->getUrl(), [
                RequestOptions::HEADERS => $request->getHeaders(),
                RequestOptions::BODY => $request->getContent(),
            ])->then([
                $promise => 'resolve',
                $promise => 'reject'
            ]);

        if (! $this->isRun) {
            $this->loop->addPeriodicTimer(0, \Closure::fromCallable([$this->queue, 'run']));
            $this->isRun = true;
        }

        return $promise;
    }
}
