<?php

declare(strict_types=1);

namespace AppTest\Slack\SlashCommand;

use App\GitHub\Event\RegisterWebhook;
use App\Slack\SlashCommand\RegisterRepoCommand;
use App\Slack\SlashCommand\SlashCommandRequest;
use App\Slack\SlashCommand\SlashCommandResponseFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;

class RegisterRepoCommandTest extends TestCase
{
    public function testDispatchesRegisterWebhookWithRequestDataAndReturnsResponse(): void
    {
        $repo        = 'laminas/laminas-repo-of-some-sort';
        $responseUrl = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';
        $response    = $this->prophesize(ResponseInterface::class)->reveal();

        $request = $this->prophesize(SlashCommandRequest::class);
        $request->text()->willReturn($repo)->shouldBeCalled();
        $request->responseUrl()->willReturn($responseUrl)->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher
            ->dispatch(Argument::that(function (RegisterWebhook $event) use ($repo, $responseUrl): RegisterWebhook {
                TestCase::assertSame($repo, $event->repo());
                TestCase::assertSame($responseUrl, $event->responseUrl());
                return $event;
            }))
            ->shouldBeCalled();

        $responseFactory = $this->prophesize(SlashCommandResponseFactory::class);
        $responseFactory
            ->createResponse(sprintf('Request to register laminas-bot webhook for %s queued', $repo))
            ->willReturn($response)
            ->shouldBeCalled();

        $command = new RegisterRepoCommand(
            $responseFactory->reveal(),
            $dispatcher->reveal()
        );

        $this->assertSame($response, $command->dispatch($request->reveal()));
    }
}
