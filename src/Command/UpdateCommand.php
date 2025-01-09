<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Command;

use Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'setono:sylius-catalog-promotion:update',
    description: 'Run a catalog promotion update',
)]
final class UpdateCommand extends Command
{
    public function __construct(private readonly MessageBusInterface $commandBus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandBus->dispatch(new StartCatalogPromotionUpdate(triggeredBy: sprintf('Running command "%s"', (string) self::getDefaultName())));

        return 0;
    }
}
