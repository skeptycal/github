<?php declare(strict_types=1);

namespace ApiClients\Client\Github\CommandBus\Handler;

use ApiClients\Client\Github\CommandBus\Command\RepositoriesCommand;
use ApiClients\Client\Github\Resource\RepositoryInterface;
use ApiClients\Client\Github\Service\IteratePagesService;
use ApiClients\Foundation\Hydrator\Hydrator;
use React\Promise\PromiseInterface;
use Rx\Observable;
use function ApiClients\Tools\Rx\unwrapObservableFromPromise;
use function React\Promise\resolve;
use function WyriHaximus\React\futureFunctionPromise;

final class RepositoriesHandler
{
    /**
     * @var IteratePagesService
     */
    private $iteratePagesService;

    /**
     * @var Hydrator
     */
    private $hydrator;

    /**
     * @param IteratePagesService $iteratePagesService
     * @param Hydrator $hydrator
     */
    public function __construct(IteratePagesService $iteratePagesService, Hydrator $hydrator)
    {
        $this->iteratePagesService = $iteratePagesService;
        $this->hydrator = $hydrator;
    }

    /**
     * Fetch the given repository and hydrate it
     *
     * @param RepositoriesCommand $command
     * @return PromiseInterface
     */
    public function handle(RepositoriesCommand $command): PromiseInterface
    {
        return resolve(unwrapObservableFromPromise(
            $this->iteratePagesService->handle('users/' . $command->getLogin() . '/repos')
        )->flatMap(function ($repositories) {
            return Observable::fromArray($repositories);
        })->map(function ($repository) {
            return $this->hydrator->hydrate(RepositoryInterface::HYDRATE_CLASS, $repository);
        }));
    }
}
