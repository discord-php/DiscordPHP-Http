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

/**
 * A transition helper from react/promise v2 to react/promise v3.
 * Please do not use this polyfill class in place of real ExtendedPromiseInterface.
 *
 * @see \React\Promise\ExtendedPromiseInterface
 *
 * @internal Used internally for DiscordPHP v10
 *
 * @since 10.4.0
 */
interface ExtendedPromiseInterface
{
    public function done(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null);

    public function otherwise(callable $onRejected);

    public function always(callable $onFulfilledOrRejected);

    public function progress(callable $onProgress);
}
