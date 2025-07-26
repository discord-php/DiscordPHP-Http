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

trait EndpointTrait
{
    /**
     * Binds a list of arguments to the endpoint.
     *
     * @param  string[] ...$args
     * @return this
     */
    public function bindArgs(...$args): self
    {
        for ($i = 0; $i < count($this->vars) && $i < count($args); $i++) {
            $this->args[$this->vars[$i]] = $args[$i];
        }

        return $this;
    }

    /**
     * Binds an associative array to the endpoint.
     *
     * @param  string[] $args
     * @return this
     */
    public function bindAssoc(array $args): self
    {
        $this->args = array_merge($this->args, $args);

        return $this;
    }

    /**
     * Adds a key-value query pair to the endpoint.
     *
     * @param string      $key
     * @param string|bool $value
     */
    public function addQuery(string $key, $value): void
    {
        if (! is_bool($value)) {
            $value = (string) $value;
        }

        $this->query[$key] = $value;
    }

    /**
     * Converts the endpoint into the absolute endpoint with
     * placeholders replaced.
     *
     * Passing a true boolean in will only replace the major parameters.
     * Used for rate limit buckets.
     *
     * @param  bool   $onlyMajorParameters
     * @return string
     */
    public function toAbsoluteEndpoint(bool $onlyMajorParameters = false): string
    {
        $endpoint = $this->endpoint;

        foreach ($this->vars as $var) {
            if (
                ! isset($this->args[$var]) ||
                (
                    $onlyMajorParameters &&
                    (method_exists($this, 'isMajorParameter') ? ! $this->isMajorParameter($var) : false)
                )
            ) {
                continue;
            }

            $endpoint = str_replace(":{$var}", $this->args[$var], $endpoint);
        }

        if (! $onlyMajorParameters && count($this->query) > 0) {
            $endpoint .= '?'.http_build_query($this->query);
        }

        return $endpoint;
    }

    /**
     * Converts the endpoint to a string.
     * Alias of ->toAbsoluteEndpoint();.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toAbsoluteEndpoint();
    }

    /**
     * Creates an endpoint class and binds arguments to
     * the newly created instance.
     *
     * @param  string   $endpoint
     * @param  string[] $args
     * @return Endpoint
     */
    public static function bind(string $endpoint, ...$args)
    {
        $endpoint = new Endpoint($endpoint);
        $endpoint->bindArgs(...$args);

        return $endpoint;
    }

    /**
     * Checks if a parameter is a major parameter.
     *
     * @param  string $param
     * @return bool
     */
    private static function isMajorParameter(string $param): bool
    {
        return in_array($param, Endpoint::MAJOR_PARAMETERS);
    }
}
