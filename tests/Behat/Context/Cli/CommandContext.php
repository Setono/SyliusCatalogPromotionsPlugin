<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use Setono\SyliusCatalogPromotionPlugin\Command\ProcessPromotionsCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

final class CommandContext implements Context
{
    /** @var KernelInterface */
    private $kernel;

    /** @var Application */
    private $application;

    /** @var ProcessPromotionsCommand */
    private $command;

    /** @var CommandTester */
    private $tester;

    public function __construct(KernelInterface $kernel, ProcessPromotionsCommand $command)
    {
        $this->kernel = $kernel;
        $this->command = $command;
    }

    /**
     * @When I run the process command
     */
    public function iRunProcessCommand(): void
    {
        $commandName = 'setono:sylius-catalog-promotion:process';

        $this->application = new Application($this->kernel);
        $this->application->add($this->command);

//        $this->command = $this->application->find($commandName);
        $this->tester = new CommandTester($this->command);

        $this->tester->execute(['command' => $commandName]);
    }

    /**
     * @Then the command should finish successfully
     */
    public function commandSuccess(): void
    {
        Assert::same($this->tester->getStatusCode(), 0);
    }
}
