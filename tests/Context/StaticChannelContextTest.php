<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Context;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCatalogPromotionPlugin\Context\StaticChannelContext;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;

class StaticChannelContextTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ChannelRepositoryInterface> */
    private ObjectProphecy $channelRepository;

    private StaticChannelContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channelRepository = $this->prophesize(ChannelRepositoryInterface::class);
        $this->context = new StaticChannelContext($this->channelRepository->reveal());
    }

    /**
     * @test
     */
    public function it_returns_valid_channel(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $this->context->setChannel($channel->reveal());
        self::assertSame($channel->reveal(), $this->context->getChannel());
    }

    /**
     * @test
     */
    public function it_returns_valid_channel_when_set_with_code(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $this->channelRepository->findOneByCode('channel_code')->willReturn($channel->reveal());

        $this->context->setChannelCode('channel_code');
        self::assertSame($channel->reveal(), $this->context->getChannel());
    }

    /**
     * @test
     */
    public function it_throws_when_no_channel_is_set(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Static channel is not set');

        $this->context->getChannel();
    }

    /**
     * @test
     */
    public function it_throws_when_channel_does_not_exist(): void
    {
        $this->expectException(ChannelNotFoundException::class);

        $this->context->setChannelCode('not_existing_channel');
    }

    /**
     * @test
     */
    public function it_allows_changing_channel(): void
    {
        $firstChannel = $this->prophesize(ChannelInterface::class);

        $secondChannel = $this->prophesize(ChannelInterface::class);

        $this->context->setChannel($firstChannel->reveal());
        self::assertSame($firstChannel->reveal(), $this->context->getChannel());

        $this->context->setChannel($secondChannel->reveal());
        self::assertSame($secondChannel->reveal(), $this->context->getChannel());
    }
}
