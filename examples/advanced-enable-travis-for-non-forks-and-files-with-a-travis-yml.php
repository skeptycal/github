<?php declare(strict_types=1);

/**
 * This example enables travis for all repositories
 * with a .travis.yml that aren't forks and aren't enabled yet.
 */

use ApiClients\Client\Github\AsyncClient;
use ApiClients\Client\Github\Resource\Async\Contents\File;
use ApiClients\Client\Github\Resource\Async\Repository;
use ApiClients\Client\Github\Resource\Contents\FileInterface;
use ApiClients\Client\Github\Resource\UserInterface;
use ApiClients\Client\Travis\AsyncClient as AsyncTravisClient;
use ApiClients\Client\Travis\AsyncClientInterface;
use ApiClients\Client\Travis\Resource\Async\Repository as TravisRepository;
use ApiClients\Foundation\Options;
use ApiClients\Foundation\Transport\Options as TransportOptions;
use ApiClients\Middleware\Delay\DelayMiddleware;
use ApiClients\Middleware\Pool\PoolMiddleware;
use React\EventLoop\Factory;
use ResourcePool\Pool;
use Rx\Observable;
use function ApiClients\Foundation\resource_pretty_print;
use function ApiClients\Tools\Rx\unwrapObservableFromPromise;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

/**
 * This example has the potential for a lot of calls, so throttling a bit.
 */
$transportOptions = [
    TransportOptions::DEFAULT_REQUEST_OPTIONS => [
        PoolMiddleware::class => [
            \ApiClients\Middleware\Pool\Options::POOL => new Pool(3),
        ],
        DelayMiddleware::class => [
            \ApiClients\Middleware\Delay\Options::DELAY => 1,
        ],
    ],
    TransportOptions::MIDDLEWARE => [
        PoolMiddleware::class,
        DelayMiddleware::class,
    ],
];

$loop = Factory::create();
$travisClient = AsyncTravisClient::create($loop, require 'resolve_travis-key.php', [
    Options::TRANSPORT_OPTIONS => $transportOptions,
]);
$githubClient = AsyncClient::create($loop, require 'resolve_token.php', [
    Options::TRANSPORT_OPTIONS => $transportOptions,
    // Pass the Travis client into the Github client internal container
    Options::CONTAINER_DEFINITIONS => [
        AsyncClientInterface::class => $travisClient,
    ],
]);

unwrapObservableFromPromise($githubClient->user($argv[1])->then(function (UserInterface $user) use ($argv) {
    resource_pretty_print($user);

    return $user->repositories();
}))->filter(function (Repository $repository) {
    return !$repository->fork();
})->filter(function (Repository $repository) {
    return $repository->size() > 0;
})->filter(function (Repository $repository) {
    return strpos($repository->name(), 'reactphp-http') === 0;
})->flatMap(function (Repository $repository) {
    return Observable::fromPromise(new React\Promise\Promise(function ($resolve, $reject) use ($repository) {
        $hasTravisYml = false;
        $repository->contents()->filter(function ($node) {
            return $node instanceof FileInterface;
        })->filter(function (File $file) {
            return $file->name() === '.travis.yml';
        })->subscribe(function () use (&$hasTravisYml) {
            $hasTravisYml = true;
        }, function ($error) use ($reject) {
            $reject($error);
        }, function () use (&$hasTravisYml, $resolve) {
            $resolve($hasTravisYml);
        });
    }))->filter(function ($hasTravisYml) {
        return !$hasTravisYml;
    })
    ->mapTo($repository);
})->flatMap(function (Repository $repository) {
    return Observable::fromPromise($repository->travisRepository());
})->flatMap(function (TravisRepository $repository) {
    return Observable::fromPromise($repository->isActive())
        ->filter(function ($isActive) {
            return !$isActive;
        })
        ->mapTo($repository);
})->subscribe(function (TravisRepository $repository) {
    $repository->enable()->done(function (TravisRepository $repository) {
        resource_pretty_print($repository);
    }, 'display_throwable');
}, 'display_throwable');

$loop->run();

displayState($githubClient->getRateLimitState());