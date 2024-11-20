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
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use React\Socket\Connector;

/**
 * react/http driver for Discord HTTP client.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class React implements DriverInterface
{
    /**
     * ReactPHP event loop.
     *
     * @var LoopInterface
     */
    protected $loop;

    /**
     * ReactPHP/HTTP browser.
     *
     * @var Browser
     */
    protected $browser;

    /**
     * Constructs the React driver.
     *
     * @param LoopInterface $loop
     * @param array         $options
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;

        // Allow 400 and 500 HTTP requests to be resolved rather than rejected.
        $browser = new Browser($loop, new Connector($loop, $options));
        $this->browser = $browser->withRejectErrorResponse(false);
    }

    public function runRequest(Request $request): PromiseInterface
    {
        return $this->browser->{$request->getMethod()}(
            $request->getUrl(),
            $request->getHeaders(),
            $request->getContent()
        );
    }
}
