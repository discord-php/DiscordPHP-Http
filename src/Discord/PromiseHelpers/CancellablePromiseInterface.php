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
  * Please do not use this polyfill class in place of real CancellablePromiseInterface.
  *
  * @see \React\Promise\CancellablePromiseInterface
  *
  * @internal Used internally for DiscordPHP v10
  *
  * @since 10.4.0
  */
interface CancellablePromiseInterface
{
    public function cancel();
}
