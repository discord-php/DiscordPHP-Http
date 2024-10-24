<?php

/*
 * This file is a part of the DiscordPHP-Http project.
 *
 * Copyright (c) 2021-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

namespace Discord\Http\PromiseHelpers;

use React\Promise\Deferred as ReactDeferred;
use React\Promise\PromiseInterface;

/**
 * A transition helper from react/promise v2 to react/promise v3.
 * Please do not use this polyfill class in place of real Deferred.
 *
 * @see \React\Promise\Deferred
 * @see PromisorInterface
 *
 * @internal Used internally for DiscordPHP v10
 *
 * @since 10.4.0
 */
final class Deferred implements PromisorInterface
{
    /**
     * The actual Promisor
     */
    public ReactDeferred $deferred;

    /**
     * Determine the installed package is Promise-v3
     */
    public static bool $isPromiseV3 = false;

    /**
     * @var PromiseInterfacePolyFill|PromiseInterfacePolyFill
     */
    private $promise;

    /**
     * @param callable|ReactDeferred $canceller Canceller callback or a Deferred to use
     *
     * @throws \InvalidArgumentException $canceller is not null or callable or a Deferred
     */
    public function __construct($canceller = null)
    {
        if ($canceller instanceof ReactDeferred) {
            $this->deferred = $canceller;
        } elseif (null === $canceller || is_callable($canceller)) {
            $this->deferred = new ReactDeferred($canceller);
        } else {
            throw new \InvalidArgumentException('$canceller must be either null or callable or Deferred');
        }
    }

    /**
     * @return PromiseInterfacePolyFill|PromiseInterface|\React\Promise\ExtendedPromiseInterface
     */
    public function promise()
    {
        if (!static::$isPromiseV3) {
            // Just use the same react/promise v2 promise
            return $this->deferred->promise();
        }

        if (null === $this->promise) {
            // Wrap with the polyfill if user installed react/promise v3
            $this->promise = new PromiseInterfacePolyFill($this->deferred->promise());
        }

        return $this->promise;
    }

    /**
     * @see React\Promise\Deferred::resolve()
     *
     * @return void
     */
    public function resolve($value = null)
    {
        $this->deferred->resolve($value);
    }

    /**
     * @see React\Promise\Deferred::reject()
     *
     * @param \Throwable $reason required in Promise-v3, will be resolved if not a throwable
     *
     * @throws \InvalidArgumentException $reason is null & react/promise is v3
     *
     * @return void
     */
    public function reject($reason = null)
    {
        if (static::$isPromiseV3) {
            if (null === $reason) {
                $reason = new \InvalidArgumentException('reject($reason) must not be null');
            } elseif (!($reason instanceof \Throwable)) {
                return $this->deferred->resolve($reason);
            }
        }

        $this->deferred->reject($reason);
    }

    /**
     * Not supported
     *
     * @deprecated
     */
    public function notify($update = null)
    {
        if (method_exists($this->deferred, 'notify')) {
            $this->deferred->notify($update);
            return;
        }

        throw new \BadMethodCallException('notify() is not supported with this polyfill and react/promise v3');
    }

    /**
     * Not supported
     *
     * @deprecated
     */
    public function progress($update = null)
    {
        if (method_exists($this->deferred, 'progress')) {
            $this->deferred->progress($update);
            return;
        }

        throw new \BadMethodCallException('progress() is not supported with this polyfill and react/promise v3');
    }
}
