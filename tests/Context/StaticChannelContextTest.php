<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Context;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCatalogPromotionPlugin\Context\StaticChannelContext;
use Sylius\Component\Channel\Model\ChannelInterface;

class StaticChannelContextTest extends TestCase
{
    use ProphecyTrait;

    private StaticChannelContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = new StaticChannelContext();
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
    public function it_throws_when_no_channel_is_set(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Static channel is not set');

        $this->context->getChannel();
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
