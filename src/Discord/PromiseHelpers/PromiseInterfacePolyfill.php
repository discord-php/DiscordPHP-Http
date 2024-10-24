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

use React\Promise\PromiseInterface;
use function React\Promise\reject;

/**
 * A transition helper from react/promise v2 to react/promise v3.
 * Please do not use this polyfill class in place of real PromiseInterface.
 *
 * @see \React\Promise\PromiseInterface
 * @see ExtendedPromiseInterface
 * @see CancellablePromiseInterface
 *
 * @internal Used internally for DiscordPHP v10
 *
 * @since 10.4.0
 */
final class PromiseInterfacePolyFill implements PromiseInterface, \React\Promise\ExtendedPromiseInterface, \React\Promise\CancellablePromiseInterface
{
    /**
     * The actual Promise, must not be this class
     */
    public PromiseInterface $promise;

    /**
     * @param \React\Promise\Promise|\React\Promise\FulfilledPromise|\React\Promise\RejectedPromise $promise
     *
     * @throws \InvalidArgumentException This polyfill cannot accept the same polyfill class.
     */
    public function __construct(PromiseInterface $promise)
    {
        if ($promise instanceof self) {
            throw new \InvalidArgumentException('Cannot use polyfill inside the polyfill class');
        }

        $this->promise = $promise;
    }

    /**
     * Converts then() and wrap it with this polyfill class.
     *
     * @see React\Promise\PromiseInterface::then()
     *
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @param callable|null $onProgress This argument must not be used anymore.
     *
     * @throws \InvalidArgumentException $onProgress is not null & react/promise is v3
     *
     * @return self|PromiseInterface
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null): PromiseInterface
    {
        if (method_exists($this->promise, 'progress')) {
            return new self($this->promise->then($onFulfilled, $onRejected, $onProgress));
        }

        if (null !== $onProgress) {
            return reject(new \InvalidArgumentException('$onProgress is not supported with this polyfill'));
        }

        return new self($this->promise->then($onFulfilled, $onRejected));
    }

    /**
     * Do not use this in v3, use then().
     *
     * @see React\Promise\ExtendedPromiseInterface::done() v2
     *
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @param callable|null $onProgress This argument must not be used anymore.
     *
     * @throws \InvalidArgumentException $onProgress is not null & react/promise is v3
     *
     * @deprecated 10.4.0 If you see this, please change done() to then()
     *
     * @return void
     */
    public function done(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        if (method_exists($this->promise, 'done')) {
            $this->promise->done($onFulfilled, $onRejected, $onProgress);
            return;
        }

        if (null !== $onProgress) {
            throw new \InvalidArgumentException('$onProgress is not supported with this polyfill and react/promise v3');
        }

        $this->promise->then($onFulfilled, $onRejected);
        return;
    }

    /**
     * @see React\Promise\ExtendedPromiseInterface::otherwise() v2
     *
     * @deprecated Promise-v3 Use catch() instead.
     */
    public function otherwise(callable $onRejected): PromiseInterface
    {
        return $this->catch($onRejected);
    }

    /**
     * @see React\Promise\ExtendedPromiseInterface::always() v2
     *
     * @deprecated Promise-v3 Use finally() instead.
     */
    public function always(callable $onFulfilledOrRejected): PromiseInterface
    {
        return $this->finally($onFulfilledOrRejected);
    }

    /**
     * @see React\Promise\CancellablePromiseInterface::cancel() v2
     * @see React\Promise\PromiseInterface::cancel() v3
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->promise->cancel();
        return;
    }

    /**
     * Not supported
     *
     * @deprecated
     */
    public function progress(callable $onProgress)
    {
        if (method_exists($this->promise, 'progress')) {
            return $this->promise->progress($onProgress);
        }

        return reject(new \BadMethodCallException('progress() is not supported with this polyfill and react/promise v3'));
    }

    /**
     * @see React\Promise\PromiseInterface::catch() v3
     * @see React\Promise\ExtendedPromiseInterface::otherwise() v2
     *
     * @param callable $onRejected
     * @return PromiseInterface
     */
    public function catch(callable $onRejected): PromiseInterface
    {
        if (method_exists($this->promise, 'catch')) {
            return $this->promise->catch($onRejected);
        }

        return $this->promise->then(null, $onRejected);
    }

    /**
     * @see React\Promise\PromiseInterface::finally() v3
     * @see React\Promise\ExtendedPromiseInterface::always() v2
     *
     * @param callable $onFulfilledOrRejected
     * @return PromiseInterface
     */
    public function finally(callable $onFulfilledOrRejected): PromiseInterface
    {
        if (method_exists($this->promise, 'finally')) {
            return $this->promise->finally($onFulfilledOrRejected);
        }

        return $this->promise->always($onFulfilledOrRejected);
    }
}
