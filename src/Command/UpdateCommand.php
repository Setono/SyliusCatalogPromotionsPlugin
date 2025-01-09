<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Command;

use Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class UpdateCommand extends Command
{
    protected static $defaultName = 'setono:sylius-catalog-promotion:update';

    protected static $defaultDescription = 'Run a catalog promotion update';

    public function __construct(private readonly MessageBusInterface $commandBus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandBus->dispatch(new StartCatalogPromotionUpdate(triggeredBy: sprintf('Running command "%s"', (string) self::$defaultName)));

        return 0;
    }
}
