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

use Composer\InstalledVersions;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use SplQueue;

/**
 * Discord HTTP client.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class Http implements HttpInterface
{
    use HttpTrait;

    /**
     * DiscordPHP-Http version.
     *
     * @var string
     */
    public const VERSION = 'v10.4.7';

    /**
     * Current Discord HTTP API version.
     *
     * @var string
     */
    public const HTTP_API_VERSION = 10;

    /**
     * Discord API base URL.
     *
     * @var string
     */
    public const BASE_URL = 'https://discord.com/api/v'.self::HTTP_API_VERSION;

    /**
     * The number of concurrent requests which can
     * be executed.
     *
     * @var int
     */
    public const CONCURRENT_REQUESTS = 5;

    /**
     * Authentication token.
     *
     * @var string
     */
    private $token;

    /**
     * Logger for HTTP requests.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * HTTP driver.
     *
     * @var DriverInterface
     */
    protected $driver;

    /**
     * ReactPHP event loop.
     *
     * @var LoopInterface
     */
    protected $loop;

    /**
     * Array of request buckets.
     *
     * @var Bucket[]
     */
    protected $buckets = [];

    /**
     * The current rate-limit.
     *
     * @var RateLimit
     */
    protected $rateLimit;

    /**
     * Timer that resets the current global rate-limit.
     *
     * @var TimerInterface
     */
    protected $rateLimitReset;

    /**
     * Request queue to prevent API
     * overload.
     *
     * @var SplQueue
     */
    protected $queue;

    /**
     * Request queue to prevent API
     * overload.
     *
     * @var SplQueue
     */
    protected $interactionQueue;

    /**
     * Number of requests that are waiting for a response.
     *
     * @var int
     */
    protected $waiting = 0;

    /**
     * Whether react/promise v3 is used, if false, using v2.
     */
    protected $promiseV3 = true;

    /**
     * Http wrapper constructor.
     *
     * @param string               $token
     * @param LoopInterface        $loop
     * @param DriverInterface|null $driver
     */
    public function __construct(string $token, LoopInterface $loop, LoggerInterface $logger, ?DriverInterface $driver = null)
    {
        $this->token = $token;
        $this->loop = $loop;
        $this->logger = $logger;
        $this->driver = $driver;
        $this->queue = new SplQueue;
        $this->interactionQueue = new SplQueue;

        $this->promiseV3 = str_starts_with(InstalledVersions::getVersion('react/promise'), '3.');
    }
}
