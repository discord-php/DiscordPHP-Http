<?php

namespace Tests\Discord\Http;

use Discord\Http\DriverInterface;
use Discord\Http\Http;
use Discord\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use React\EventLoop\Factory;
use React\Http\Message\Response;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Characterizes the existing behavior, NOT acceptance tests for a lifecycle fix.
 * No sockets, timers, credentials, sleeps, or event-loop run are needed.
 */
class RequestLifecycleCharacterizationTest extends TestCase
{
    public function testCancellingAQueuedPromiseDoesNotPreventDispatch(): void
    {
        [$http, $driver] = $this->http();
        $first = $http->get('users/@me');
        $queued = $http->get('users/@me');
        $this->assertCount(1, $driver->pending);

        $queued->cancel();
        $driver->pending[0]->resolve(new Response(204));

        $this->assertCount(2, $driver->pending);
        $driver->pending[1]->resolve(new Response(204));
    }

    public function testCancellingAnActivePromiseDoesNotCancelTheDriver(): void
    {
        [$http, $driver] = $this->http();
        $promise = $http->get('users/@me');
        $promise->cancel();

        $this->assertSame(0, $driver->cancellations);
        $driver->pending[0]->resolve(new Response(204));
    }

    public function testGatewayErrorsImmediatelyStartAnotherAttempt(): void
    {
        [$http, $driver] = $this->http();
        $promise = $http->get('users/@me');
        $settled = false;
        $promise->then(function () use (&$settled) { $settled = true; });

        // Finite demonstration of retry behavior; not an infinite-loop test.
        foreach ([502, 525, 502, 525] as $index => $status) {
            $driver->pending[$index]->resolve(new Response($status));
            $this->assertCount($index + 2, $driver->pending);
            $this->assertSame($driver->requests[0], $driver->requests[$index + 1]);
            $this->assertFalse($settled);
        }

        $driver->pending[4]->resolve(new Response(204));
        $this->assertTrue($settled);
    }

    private function http(): array
    {
        $driver = new class implements DriverInterface {
            public $pending = [];
            public $requests = [];
            public $cancellations = 0;

            public function runRequest(Request $request): PromiseInterface
            {
                $this->requests[] = $request;
                $deferred = new Deferred(function () { ++$this->cancellations; });
                $this->pending[] = $deferred;

                return $deferred->promise();
            }
        };

        return [new Http('', Factory::create(), new NullLogger(), $driver), $driver];
    }
}
