<?php

use ApiClients\Client\Github\Client;
use function ApiClients\Foundation\resource_pretty_print;
use ApiClients\Tools\ResourceTestUtilities\Types;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

$client = new Client();

$users = [
    'WyriHaximus',
];

if (count($argv) > 1) {
    unset($argv[0]);
    foreach ($argv as $user) {
        $users[] = $user;
    }
}

foreach ($users as $user) {
    resource_pretty_print($client->user($user));
}
