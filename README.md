# DiscordPHP-Http

Asynchronous HTTP client used for communication with the Discord REST API.

## Requirements

- PHP >=7.2

## Installation

```sh
$ composer require discord-php/http
```

A [psr/log](https://packagist.org/packages/psr/log)-compliant logging library is also required. We recommend [monolog](https://github.com/Seldaek/monolog) which will be used in examples.

## Usage

```php
<?php

include 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Discord\Http\Http;
use Discord\Http\Drivers\React;

$loop = \React\EventLoop\Factory::create();
$logger = (new Logger('logger-name'))->pushHandler(new StreamHandler('php://output'));
$http = new Http(
    'Bot xxxx.yyyy.zzzz',
    $loop,
    $logger
);

// set up a driver - this example uses the React driver
$driver = new React($loop);
$http->setDriver($driver);

// must be the last line
$loop->run();
```

All request methods have the same footprint:

```php
$http->get(string $url, $content = null, array $headers = []);
$http->post(string $url, $content = null, array $headers = []);
$http->put(string $url, $content = null, array $headers = []);
$http->patch(string $url, $content = null, array $headers = []);
$http->delete(string $url, $content = null, array $headers = []);
```

For other methods:

```php
$http->queueRequest(string $method, string $url, $content, array $headers = []);
```

All methods return the decoded JSON response in an object:

```php
// https://discord.com/api/v8/oauth2/applications/@me
$http->get('oauth2/applications/@me')->done(function ($response) {
    var_dump($response);
}, function ($e) {
    echo "Error: ".$e->getMessage().PHP_EOL;
});
```

## License

This software is licensed under the MIT license which can be viewed in the LICENSE.md file.

## Credits

- [David Cole](mailto:david.cole1340@gmail.com)
- All contributors
