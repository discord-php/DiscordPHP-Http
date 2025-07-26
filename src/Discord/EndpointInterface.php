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

interface EndpointInterface
{
    public function bindArgs(...$args): self;
    public function bindAssoc(array $args): self;
    public function addQuery(string $key, $value): void;
    public function toAbsoluteEndpoint(bool $onlyMajorParameters = false): string;
    public function __toString(): string;
    public static function bind(string $endpoint, ...$args);
}
