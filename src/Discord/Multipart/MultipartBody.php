<?php

/*
 * This file is a part of the DiscordPHP-Http project.
 *
 * Copyright (c) 2021-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

namespace Discord\Http\Multipart;

use Ramsey\Uuid\Uuid;

class MultipartBody
{
    public const BOUNDARY_PREFIX = 'DISCORDPHP-HTTP-BOUNDARY';

    private array $fields;
    private string $boundary;

    private function createBoundary(): string
    {
        return self::BOUNDARY_PREFIX.'-'.strtoupper((string) Uuid::uuid1());
    }

    /**
     * @var MultipartField[]
     */
    public function __construct(array $fields, ?string $boundary = null)
    {
        $this->fields = $fields;
        $this->boundary = $boundary ?? $this->createBoundary();
    }

    public function __toString(): string
    {
        $prefixedBoundary = '--'.$this->boundary;
        $boundaryEnd = $prefixedBoundary.'--';

        $convertedFields = array_map(
            function (MultipartField $field) {
                return (string) $field;
            },
            $this->fields
        );

        $fieldsString = implode(PHP_EOL.$prefixedBoundary.PHP_EOL, $convertedFields);

        return implode(PHP_EOL, [
            $prefixedBoundary,
            $fieldsString,
            $boundaryEnd,
        ]);
    }

    public function getHeaders(): array
    {
        return [
            'Content-Type' => 'multipart/form-data; boundary='.$this->boundary,
            'Content-Length' => strlen((string) $this),
        ];
    }
}
