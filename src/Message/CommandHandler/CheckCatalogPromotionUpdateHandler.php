<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\CheckCatalogPromotionUpdate;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Setono\SyliusCatalogPromotionPlugin\Workflow\CatalogPromotionUpdateWorkflow;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Workflow\WorkflowInterface;
use Webmozart\Assert\Assert;

final class CheckCatalogPromotionUpdateHandler
{
    use ORMTrait;

    public function __construct(
        private readonly WorkflowInterface $catalogPromotionUpdateWorkflow,
        ManagerRegistry $managerRegistry,
        private readonly ?ClockInterface $clock,
        /** @var class-string<CatalogPromotionUpdateInterface> $catalogPromotionUpdateClass */
        private readonly string $catalogPromotionUpdateClass,
        private readonly int $maxTries = 10,
        private readonly int $maxRetrySeconds = 43_200, // 12 hours
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(CheckCatalogPromotionUpdate $message): void
    {
        ++$message->tries;

        $catalogPromotionUpdate = $this->getCatalogPromotionUpdate($message->catalogPromotionUpdate);

        if ($catalogPromotionUpdate->getState() !== CatalogPromotionUpdateInterface::STATE_PROCESSING) {
            throw new UnrecoverableMessageHandlingException(sprintf(
                'Catalog promotion update with id %d is not in the "%s" state',
                $message->catalogPromotionUpdate,
                CatalogPromotionUpdateInterface::STATE_PROCESSING,
            ));
        }

        try {
            // todo I think the 'retry logic' belongs in some middleware where we can get the retry count from the envelope
            if (!$catalogPromotionUpdate->hasAllMessagesBeenProcessed()) {
                if ($message->tries >= $this->maxTries) {
                    throw new UnrecoverableMessageHandlingException(sprintf(
                        'Catalog promotion update with id %s has not processed all messages after %d tries',
                        $message->catalogPromotionUpdate,
                        $this->maxTries,
                    ));
                }

                $createdAt = $catalogPromotionUpdate->getCreatedAt();
                Assert::notNull($createdAt);
                $createdAt = \DateTimeImmutable::createFromInterface($createdAt);

                if (($this->clock?->now() ?? new \DateTimeImmutable()) >= $createdAt->add(new \DateInterval(sprintf('PT%dS', $this->maxRetrySeconds)))) {
                    throw new UnrecoverableMessageHandlingException(sprintf(
                        'Catalog promotion update with id %d has not processed all messages after %d seconds',
                        $message->catalogPromotionUpdate,
                        $this->maxRetrySeconds,
                    ));
                }

                throw new RecoverableMessageHandlingException(sprintf(
                    'Catalog promotion update with id %s has not processed all messages',
                    $message->catalogPromotionUpdate,
                ));
            }

            $this->catalogPromotionUpdateWorkflow->apply($catalogPromotionUpdate, CatalogPromotionUpdateWorkflow::TRANSITION_COMPLETE);
        } catch (UnrecoverableMessageHandlingException $e) {
            $this->catalogPromotionUpdateWorkflow->apply($catalogPromotionUpdate, CatalogPromotionUpdateWorkflow::TRANSITION_FAIL);

            $catalogPromotionUpdate->setError($e->getMessage());

            throw $e;
        } finally {
            $manager = $this->getManager($this->catalogPromotionUpdateClass);
            $manager->flush();
        }
    }

    private function getCatalogPromotionUpdate(int $id): CatalogPromotionUpdateInterface
    {
        $catalogPromotionUpdate = $this->getManager($this->catalogPromotionUpdateClass)->find($this->catalogPromotionUpdateClass, $id);
        if (null === $catalogPromotionUpdate) {
            throw new UnrecoverableMessageHandlingException(sprintf('Catalog promotion update with id %d not found', $id));
        }

        return $catalogPromotionUpdate;
    }
}
