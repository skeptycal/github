<?php declare(strict_types=1);

namespace ApiClients\Client\Github\Resource\Async;

use ApiClients\Client\Github\CommandBus\Command\RefreshCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\AddLabelCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\AddWebHookCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\AppVeyorCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\BranchesCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\CommitsCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\CommunityHealthCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\Contents\FileUploadCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\ContentsCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\LabelsCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\LanguagesCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\ReleasesCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\ReplaceTopicsCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\ScrutinizerCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\SubscribeCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\TagsCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\TravisCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\UnSubscribeCommand;
use ApiClients\Client\Github\CommandBus\Command\Repository\UpdateSettingsCommand;
use ApiClients\Client\Github\CommandBus\Command\WebHooksCommand;
use ApiClients\Client\Github\Resource\Repository as BaseRepository;
use function ApiClients\Tools\Rx\unwrapObservableFromPromise;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;
use Rx\Observable;
use Rx\ObservableInterface;

class Repository extends BaseRepository
{
    public function refresh(): PromiseInterface
    {
        return $this->handleCommand(
            new RefreshCommand($this)
        );
    }

    public function branches(): ObservableInterface
    {
        return unwrapObservableFromPromise($this->handleCommand(
            new BranchesCommand($this->fullName())
        ));
    }

    public function commits(): ObservableInterface
    {
        return unwrapObservableFromPromise($this->handleCommand(
            new CommitsCommand($this->fullName())
        ));
    }

    public function labels(): ObservableInterface
    {
        return unwrapObservableFromPromise($this->handleCommand(
            new LabelsCommand($this->fullName())
        ));
    }

    public function addLabel(string $name, string $colour): PromiseInterface
    {
        return $this->handleCommand(
            new AddLabelCommand($this->fullName(), $name, $colour)
        );
    }

    public function contents(string $path = '/'): Observable
    {
        return unwrapObservableFromPromise(
            $this->handleCommand(
                new ContentsCommand($this->fullName(), $path)
            )
        );
    }

    public function communityHealth(): PromiseInterface
    {
        return $this->handleCommand(
            new CommunityHealthCommand($this->fullName())
        );
    }

    public function tags(): ObservableInterface
    {
        return unwrapObservableFromPromise($this->handleCommand(
            new TagsCommand($this->fullName())
        ));
    }

    public function releases(): ObservableInterface
    {
        return unwrapObservableFromPromise($this->handleCommand(
            new ReleasesCommand($this->fullName())
        ));
    }

    public function languages(): PromiseInterface
    {
        return $this->handleCommand(
            new LanguagesCommand($this->fullName())
        );
    }

    public function webHooks(): ObservableInterface
    {
        return unwrapObservableFromPromise($this->handleCommand(
            new WebHooksCommand($this->fullName(), 'repos')
        ));
    }

    public function addWebHook(
        string $name,
        array $config,
        array $events,
        bool $active
    ): PromiseInterface {
        return $this->handleCommand(
            new AddWebHookCommand(
                $this->fullName(),
                $name,
                $config,
                $events,
                $active
            )
        );
    }

    public function addFile(
        string $filename,
        ReadableStreamInterface $stream,
        string $commitMessage = '',
        string $branch = ''
    ): PromiseInterface {
        if ($commitMessage === '') {
            $commitMessage = 'Update ' . $this->name;
        }

        return $this->handleCommand(new FileUploadCommand(
            $this->full_name,
            $commitMessage,
            '/repos/' . $this->full_name . '/contents/' . $filename,
            '',
            $branch,
            $stream
        ));
    }

    public function subscribe(bool $subscribed = true, bool $ignored = false): PromiseInterface
    {
        return $this->handleCommand(
            new SubscribeCommand($this->fullName(), $subscribed, $ignored)
        );
    }

    public function unSubscribe(): PromiseInterface
    {
        return $this->handleCommand(
            new UnSubscribeCommand($this->fullName())
        );
    }

    public function replaceTopics(string ...$topics): PromiseInterface
    {
        return $this->handleCommand(
            new ReplaceTopicsCommand($this->fullName(), ...$topics)
        );
    }

    public function travisRepository(): PromiseInterface
    {
        return $this->handleCommand(new TravisCommand($this->fullName()));
    }

    public function appVeyorRepository(): PromiseInterface
    {
        return $this->handleCommand(new AppVeyorCommand($this->fullName()));
    }

    public function scrutinizerRepository(): PromiseInterface
    {
        return $this->handleCommand(new ScrutinizerCommand(...\explode('/', $this->fullName())));
    }

    public function updateSettings(array $settings): PromiseInterface
    {
        if (!isset($settings['name'])) {
            $settings['name'] = $this->name();
        }

        return $this->handleCommand(new UpdateSettingsCommand($this->fullName(), $settings));
    }
}
