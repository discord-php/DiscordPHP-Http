<?php

/*
 * This file is a part of the DiscordPHP-Http project.
 *
 * Copyright (c) 2020-present David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the GNU General Public License v3.0 or later
 * that is bundled with this source code in the LICENSE.md file.
 */

namespace Discord\Http\Exceptions;

use Exception;

/**
 * Thrown when a request to Discord's REST API fails.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class RequestFailedException extends Exception
{
}
