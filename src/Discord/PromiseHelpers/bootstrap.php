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
 * Interface aliases for Promise-v2 to Promise-v3 backward compatibility.
 *
 * @since 10.4.0
 */
if (!interface_exists('\React\Promise\PromisorInterface')) {
    Deferred::$isPromiseV3 = true;
    class_alias(PromisorInterface::class, '\React\Promise\PromisorInterface');
}
if (!interface_exists('\React\Promise\ExtendedPromiseInterface')) {
    class_alias(ExtendedPromiseInterface::class, '\React\Promise\ExtendedPromiseInterface');
}
if (!interface_exists('\React\Promise\CancellablePromiseInterface')) {
    class_alias(CancellablePromiseInterface::class, '\React\Promise\CancellablePromiseInterface');
}
