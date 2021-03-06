<?php declare(strict_types=1);

use ApiClients\Client\Github\AsyncClient;
use ApiClients\Client\Github\Resource\Async\Repository;
use ApiClients\Client\Github\Resource\UserInterface;
use function ApiClients\Foundation\resource_pretty_print;
use React\EventLoop\Factory;

require \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'vendor/autoload.php';

$loop = Factory::create();
$client = AsyncClient::create($loop, require 'resolve_token.php');

$client->user($argv[1] ?? 'WyriHaximus')->then(function (UserInterface $user) use ($argv) {
    resource_pretty_print($user);

    return $user->repository($argv[2] ?? 'awesome-volkswagen');
})->then(function (Repository $repository) use ($argv) {
    resource_pretty_print($repository);

    return $repository->replaceTopics(
        (string)($argv[3] ?? 'test-' . \time()),
        (string)($argv[4] ?? \random_int(100000, 9999999))
    );
})->done(function (array $topics) {
    \var_export($topics);
    echo 'Done!', \PHP_EOL;
}, 'display_throwable');

$loop->run();

displayState($client->getRateLimitState());
