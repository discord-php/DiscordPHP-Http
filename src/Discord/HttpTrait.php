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

use Discord\Http\Exceptions\BadRequestException;
use Discord\Http\Exceptions\ContentTooLongException;
use Discord\Http\Exceptions\InvalidTokenException;
use Discord\Http\Exceptions\MethodNotAllowedException;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Http\Exceptions\NotFoundException;
use Discord\Http\Exceptions\RateLimitException;
use Discord\Http\Exceptions\RequestFailedException;
use Discord\Http\Multipart\MultipartBody;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Discord HTTP client.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
trait HttpTrait
{
    /**
     * Sets the driver of the HTTP client.
     *
     * @param DriverInterface $driver
     */
    public function setDriver(DriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Runs a GET request.
     *
     * @param string|Endpoint $url
     * @param mixed           $content
     * @param array           $headers
     *
     * @return PromiseInterface
     */
    public function get($url, $content = null, array $headers = []): PromiseInterface
    {
        if (! ($url instanceof Endpoint)) {
            $url = Endpoint::bind($url);
        }

        return $this->queueRequest('get', $url, $content, $headers);
    }

    /**
     * Runs a POST request.
     *
     * @param string|Endpoint $url
     * @param mixed           $content
     * @param array           $headers
     *
     * @return PromiseInterface
     */
    public function post($url, $content = null, array $headers = []): PromiseInterface
    {
        if (! ($url instanceof Endpoint)) {
            $url = Endpoint::bind($url);
        }

        return $this->queueRequest('post', $url, $content, $headers);
    }

    /**
     * Runs a PUT request.
     *
     * @param string|Endpoint $url
     * @param mixed           $content
     * @param array           $headers
     *
     * @return PromiseInterface
     */
    public function put($url, $content = null, array $headers = []): PromiseInterface
    {
        if (! ($url instanceof Endpoint)) {
            $url = Endpoint::bind($url);
        }

        return $this->queueRequest('put', $url, $content, $headers);
    }

    /**
     * Runs a PATCH request.
     *
     * @param string|Endpoint $url
     * @param mixed           $content
     * @param array           $headers
     *
     * @return PromiseInterface
     */
    public function patch($url, $content = null, array $headers = []): PromiseInterface
    {
        if (! ($url instanceof Endpoint)) {
            $url = Endpoint::bind($url);
        }

        return $this->queueRequest('patch', $url, $content, $headers);
    }

    /**
     * Runs a DELETE request.
     *
     * @param string|Endpoint $url
     * @param mixed           $content
     * @param array           $headers
     *
     * @return PromiseInterface
     */
    public function delete($url, $content = null, array $headers = []): PromiseInterface
    {
        if (! ($url instanceof Endpoint)) {
            $url = Endpoint::bind($url);
        }

        return $this->queueRequest('delete', $url, $content, $headers);
    }

    /**
     * Builds and queues a request.
     *
     * @param string   $method
     * @param Endpoint $url
     * @param mixed    $content
     * @param array    $headers
     *
     * @return PromiseInterface
     */
    public function queueRequest(string $method, Endpoint $url, $content, array $headers = []): PromiseInterface
    {
        $deferred = new Deferred();

        if (is_null($this->driver)) {
            $deferred->reject(new \Exception('HTTP driver is missing.'));

            return $deferred->promise();
        }

        $headers = array_merge($headers, [
            'User-Agent' => $this->getUserAgent(),
            'Authorization' => $this->token,
            'X-Ratelimit-Precision' => 'millisecond',
        ]);

        $baseHeaders = [
            'User-Agent' => $this->getUserAgent(),
            'Authorization' => $this->token,
            'X-Ratelimit-Precision' => 'millisecond',
        ];

        if (! is_null($content) && ! isset($headers['Content-Type'])) {
            $baseHeaders = array_merge(
                $baseHeaders,
                $this->guessContent($content)
            );
        }

        $headers = array_merge($baseHeaders, $headers);

        $request = new Request($deferred, $method, $url, $content ?? '', $headers);
        $this->sortIntoBucket($request);

        return $deferred->promise();
    }

    /**
     * Guesses the headers and transforms the content of a request.
     *
     * @param mixed $content
     */
    protected function guessContent(&$content)
    {
        if ($content instanceof MultipartBody) {
            $headers = $content->getHeaders();
            $content = (string) $content;

            return $headers;
        }

        $content = json_encode($content);

        return [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($content),
        ];
    }

    /**
     * Executes a request.
     *
     * @param Request       $request
     * @param Deferred|null $deferred
     *
     * @return PromiseInterface
     */
    protected function executeRequest(Request $request, ?Deferred $deferred = null): PromiseInterface
    {
        if ($deferred === null) {
            $deferred = new Deferred();
        }

        if ($this->rateLimit) {
            $deferred->reject($this->rateLimit);

            return $deferred->promise();
        }

        // Promises v3 changed `->then` to behave as `->done` and removed `->then`. We still need the behaviour of `->done` in projects using v2
        $this->driver->runRequest($request)->{$this->promiseV3 ? 'then' : 'done'}(function (ResponseInterface $response) use ($request, $deferred) {
            $data = json_decode((string) $response->getBody());
            $statusCode = $response->getStatusCode();

            // Discord Rate-limit
            if ($statusCode == 429) {
                if (! isset($data->global)) {
                    if ($response->hasHeader('X-RateLimit-Global')) {
                        $data->global = $response->getHeader('X-RateLimit-Global')[0] == 'true';
                    } else {
                        // Some other 429
                        $this->logger->error($request.' does not contain global rate-limit value');
                        $rateLimitError = new RateLimitException('No rate limit global response', $statusCode);
                        $deferred->reject($rateLimitError);
                        $request->getDeferred()->reject($rateLimitError);

                        return;
                    }
                }

                if (! isset($data->retry_after)) {
                    if ($response->hasHeader('Retry-After')) {
                        $data->retry_after = $response->getHeader('Retry-After')[0];
                    } else {
                        // Some other 429
                        $this->logger->error($request.' does not contain retry after rate-limit value');
                        $rateLimitError = new RateLimitException('No rate limit retry after response', $statusCode);
                        $deferred->reject($rateLimitError);
                        $request->getDeferred()->reject($rateLimitError);

                        return;
                    }
                }

                $rateLimit = new RateLimit($data->global, $data->retry_after);
                $this->logger->warning($request.' hit rate-limit: '.$rateLimit);

                if ($rateLimit->isGlobal() && ! $this->rateLimit) {
                    $this->rateLimit = $rateLimit;
                    $this->rateLimitReset = $this->loop->addTimer($rateLimit->getRetryAfter(), function () {
                        $this->rateLimit = null;
                        $this->rateLimitReset = null;
                        $this->logger->info('global rate-limit reset');

                        // Loop through all buckets and check for requests
                        foreach ($this->buckets as $bucket) {
                            $bucket->checkQueue();
                        }
                    });
                }

                $deferred->reject($rateLimit->isGlobal() ? $this->rateLimit : $rateLimit);
            }
            // Bad Gateway
            // Cloudflare SSL Handshake error
            // Push to the back of the bucket to be retried.
            elseif ($statusCode == 502 || $statusCode == 525) {
                $this->logger->warning($request.' 502/525 - retrying request');

                $this->executeRequest($request, $deferred);
            }
            // Any other unsuccessful status codes
            elseif ($statusCode < 200 || $statusCode >= 300) {
                $error = $this->handleError($response);
                $this->logger->warning($request.' failed: '.$error);

                $deferred->reject($error);
                $request->getDeferred()->reject($error);
            }
            // All is well
            else {
                $this->logger->debug($request.' successful');

                $deferred->resolve($response);
                $request->getDeferred()->resolve($data);
            }
        }, function (\Exception $e) use ($request, $deferred) {
            $this->logger->warning($request.' failed: '.$e->getMessage());

            $deferred->reject($e);
            $request->getDeferred()->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * Sorts a request into a bucket.
     *
     * @param Request $request
     */
    protected function sortIntoBucket(Request $request): void
    {
        $bucket = $this->getBucket($request->getBucketID());
        $bucket->enqueue($request);
    }

    /**
     * Gets a bucket.
     *
     * @param string $key
     *
     * @return Bucket
     */
    protected function getBucket(string $key): Bucket
    {
        if (! isset($this->buckets[$key])) {
            $bucket = new Bucket($key, $this->loop, $this->logger, function (Request $request) {
                $deferred = new Deferred();
                self::isInteractionEndpoint($request)
                    ? $this->interactionQueue->enqueue([$request, $deferred])
                    : $this->queue->enqueue([$request, $deferred]);
                $this->checkQueue();

                return $deferred->promise();
            });

            $this->buckets[$key] = $bucket;
        }

        return $this->buckets[$key];
    }

    /**
     * Checks the request queue to see if more requests can be
     * sent out.
     */
    protected function checkQueue(bool $check_interactions = true): void
    {
        if ($check_interactions) {
            $this->checkInteractionQueue();
        }

        if ($this->waiting >= Http::CONCURRENT_REQUESTS || $this->queue->isEmpty()) {
            $this->logger->debug('http not checking queue', ['waiting' => $this->waiting, 'empty' => $this->queue->isEmpty()]);

            return;
        }

        /**
         * @var Request  $request
         * @var Deferred $deferred
         */
        [$request, $deferred] = $this->queue->dequeue();
        ++$this->waiting;

        $this->executeRequest($request)->then(function ($result) use ($deferred) {
            --$this->waiting;
            $this->checkQueue(false);
            $deferred->resolve($result);
        }, function ($e) use ($deferred) {
            --$this->waiting;
            $this->checkQueue(false);
            $deferred->reject($e);
        });
    }

    /**
     * Checks the interaction queue to see if more requests can be
     * sent out.
     */
    protected function checkInteractionQueue(): void
    {
        if ($this->interactionQueue->isEmpty()) {
            $this->logger->debug('http not checking interaction queue', ['waiting' => $this->waiting, 'empty' => $this->interactionQueue->isEmpty()]);

            return;
        }

        /**
         * @var Request  $request
         * @var Deferred $deferred
         */
        [$request, $deferred] = $this->interactionQueue->dequeue();

        $this->executeRequest($request)->then(function ($result) use ($deferred) {
            $this->checkQueue();
            $deferred->resolve($result);
        }, function ($e) use ($deferred) {
            $this->checkQueue();
            $deferred->reject($e);
        });
    }

    /**
     * Checks if the request is for an interaction endpoint.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#endpoints
     *
     * @param  Request $request
     * @return bool
     */
    public static function isInteractionEndpoint(Request $request): bool
    {
        return strpos($request->getUrl(), '/interactions') === 0;
    }

    /**
     * Returns an exception based on the request.
     *
     * @param ResponseInterface $response
     *
     * @return \Throwable
     */
    public function handleError(ResponseInterface $response): \Throwable
    {
        $reason = $response->getReasonPhrase().' - ';

        $errorBody = (string) $response->getBody();
        $errorCode = $response->getStatusCode();

        // attempt to prettyify the response content
        if (($content = json_decode($errorBody)) !== null) {
            if (! empty($content->code)) {
                $errorCode = $content->code;
            }
            $reason .= json_encode($content, JSON_PRETTY_PRINT);
        } else {
            $reason .= $errorBody;
        }

        switch ($response->getStatusCode()) {
            case 400:
                return new BadRequestException($reason, $errorCode);
            case 401:
                return new InvalidTokenException($reason, $errorCode);
            case 403:
                return new NoPermissionsException($reason, $errorCode);
            case 404:
                return new NotFoundException($reason, $errorCode);
            case 405:
                return new MethodNotAllowedException($reason, $errorCode);
            case 500:
                if (strpos(strtolower($errorBody), 'longer than 2000 characters') !== false ||
                    strpos(strtolower($errorBody), 'string value is too long') !== false) {
                    // Response was longer than 2000 characters and was blocked by Discord.
                    return new ContentTooLongException('Response was more than 2000 characters. Use another method to get this data.', $errorCode);
                }
            default:
                return new RequestFailedException($reason, $errorCode);
        }
    }

    /**
     * Returns the User-Agent of the HTTP client.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return 'DiscordBot (https://github.com/discord-php/DiscordPHP-HTTP, '.Http::VERSION.')';
    }
}
