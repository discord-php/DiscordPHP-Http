<?php

/*
 * This file is a part of the DiscordPHP-Http project.
 *
 * Copyright (c) 2021-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

namespace Tests\Discord\Http;

use Discord\Http\DriverInterface;
use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use React\EventLoop\Loop;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;

use function React\Async\await;
use function React\Promise\resolve;

class HttpTest extends TestCase
{
    public function testTheTokenIsSentAsAuthorization()
    {
        [$http, $driver] = $this->http('Bot abc');

        await($http->post(Endpoint::bind(Endpoint::LOBBIES), ['metadata' => []]));

        $this->assertSame('Bot abc', $driver->requests[0]->getHeaders()['Authorization']);
    }

    public function testAClientWithoutATokenSendsNoAuthorization()
    {
        // For routes that take the application's credentials in the body.
        [$http, $driver] = $this->http('');

        await($http->post(Endpoint::bind(Endpoint::PARTNER_SDK_TOKEN), ['client_id' => '1', 'client_secret' => 's']));

        $this->assertArrayNotHasKey('Authorization', $driver->requests[0]->getHeaders());
        $this->assertSame('application/json', $driver->requests[0]->getHeaders()['Content-Type']);
    }

    public function testTheDriverCanBeShared()
    {
        [$http, $driver] = $this->http('Bot abc');

        $this->assertSame($driver, $http->getDriver());
    }

    /**
     * @return array{0: Http, 1: object}
     */
    private function http(string $token): array
    {
        $driver = new class implements DriverInterface {
            /** @var Request[] */
            public array $requests = [];

            public function runRequest(Request $request): PromiseInterface
            {
                $this->requests[] = $request;

                return resolve(new Response(204));
            }
        };

        return [new Http($token, Loop::get(), new NullLogger(), $driver), $driver];
    }
}
