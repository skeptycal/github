<?php declare(strict_types=1);
use ApiClients\Client\Github\AsyncClient;
use ApiClients\Client\Github\Resource\Async\Contents\File;
use ApiClients\Client\Github\Resource\Async\Repository;
use ApiClients\Client\Github\Resource\Async\User;
use ApiClients\Client\Github\Resource\Contents\FileInterface;
use function ApiClients\Foundation\resource_pretty_print;
use React\EventLoop\Factory;

require \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'vendor/autoload.php';

$loop = Factory::create();

$client = AsyncClient::create($loop, require 'resolve_token.php');

$client->user($argv[1] ?? 'php-api-clients')->then(function (User $user) use ($argv) {
    resource_pretty_print($user);

    return $user->repository($argv[2] ?? 'github');
})->then(function (Repository $repository) {
    $repository->contents()->filter(function ($resource) {
        return $resource instanceof FileInterface;
    })->take(1)->subscribe(function ($content) {
        $content->refresh()->done(function (File $content) {
            resource_pretty_print($content, 1, true);
            echo \PHP_EOL, $content->decodedContent(), \PHP_EOL;
        });
    }, function ($error) {
        echo (string)$error;
    });
})->done(null, 'display_throwable');

$loop->run();

displayState($client->getRateLimitState());
