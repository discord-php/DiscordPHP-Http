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

/**
 * Thrown when the Discord servers return `content longer than 2000 characters` after
 * a REST request. The user must use WebSockets to obtain this data if they need it.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class ContentTooLongException extends RequestFailedException
{
}
