<?php

namespace Tests\Discord\Http;

use Discord\Http\DriverInterface;
use Discord\Http\Request;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

use function React\Async\await;

abstract class DriverInterfaceTest extends TestCase
{
    abstract protected function getDriver(): DriverInterface;

    private function getRequest(
        string $method,
        string $url,
        string $content = '',
        array $headers = []
    ): Request {
        $request = Mockery::mock(Request::class);

        $request->shouldReceive([
            'getMethod' => $method,
            'getUrl' => $url,
            'getContent' => $content,
            'getHeaders' => $headers,
        ]);

        return $request;
    }

    /**
     * @dataProvider requestProvider
     */
    public function testRequest(string $method, string $url, array $content = [], array $verify = [])
    {
        $driver = $this->getDriver();
        $request = $this->getRequest(
            $method,
            $url,
            $content === [] ? '' : json_encode($content),
            empty($content) ? [] : ['Content-Type' => 'Application/Json']
        );

        /** @var ResponseInterface */
        $response = await($driver->runRequest($request));

        $this->assertNotEquals('', $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());

        $jsonDecodedBody = json_decode($response->getBody(), true);
        foreach ($verify as $field => $expectedValue) {
            $this->assertEquals(
                $expectedValue,
                $jsonDecodedBody[$field]
            );
        }
    }

    public function requestProvider(): array
    {
        $content = ['something' => 'value'];
        return [
            'Plain get' => [
                'method' => 'GET',
                'url' => 'https://postman-echo.com/get',
            ],
            'Get with params' => [
                'method' => 'GET',
                'url' => 'https://postman-echo.com/get?something=value',
                'verify' => [
                    'args' => ['something' => 'value'],
                ],
            ],

            'Plain post' => [
                'method' => 'POST',
                'url' => 'https://postman-echo.com/post',
            ],
            'Post with content' => [
                'method' => 'POST',
                'url' => 'https://postman-echo.com/post',
                'content' => $content,
                'verify' => [
                    'json' => $content,
                ],
            ],

            'Plain put' => [
                'method' => 'PUT',
                'url' => 'https://postman-echo.com/put',
            ],
            'Put with content' => [
                'method' => 'PUT',
                'url' => 'https://postman-echo.com/put',
                'content' => $content,
                'verify' => [
                    'json' => $content,
                ],
            ],

            'Plain patch' => [
                'method' => 'PATCH',
                'url' => 'https://postman-echo.com/patch',
            ],
            'Patch with content' => [
                'method' => 'PATCH',
                'url' => 'https://postman-echo.com/patch',
                'content' => $content,
                'verify' => [
                    'json' => $content,
                ],
            ],

            'Plain delete' => [
                'method' => 'DELETE',
                'url' => 'https://postman-echo.com/delete',
            ],
            'Delete with content' => [
                'method' => 'DELETE',
                'url' => 'https://postman-echo.com/delete',
                'content' => $content,
                'verify' => [
                    'json' => $content,
                ],
            ],
        ];
    }
}
