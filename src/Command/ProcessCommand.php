<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Command;

use Setono\SyliusCatalogPromotionPlugin\Message\Command\ProcessCatalogPromotions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class ProcessCommand extends Command
{
    protected static $defaultName = 'setono:sylius-catalog-promotion:process';

    protected static $defaultDescription = 'Process catalog promotions';

    public function __construct(private readonly MessageBusInterface $commandBus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandBus->dispatch(new ProcessCatalogPromotions());

        return 0;
    }
}
