<?php

/*
 * This file is a part of the DiscordPHP-Http project.
 *
 * Copyright (c) 2021-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

namespace Tests\Discord\Http\Drivers;

use Discord\Http\DriverInterface;
use Discord\Http\Drivers\React;
use React\EventLoop\Loop;
use Tests\Discord\Http\DriverInterfaceTest;

class ReactTest extends DriverInterfaceTest
{
    protected function getDriver(): DriverInterface
    {
        return new React(Loop::get());
    }
}
