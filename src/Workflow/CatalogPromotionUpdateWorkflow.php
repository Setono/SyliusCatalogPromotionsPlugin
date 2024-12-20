<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Workflow;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Symfony\Component\Workflow\Transition;

final class CatalogPromotionUpdateWorkflow
{
    private const PROPERTY_NAME = 'state';

    final public const NAME = 'setono_sylius_catalog_promotion__catalog_promotion_update';

    final public const TRANSITION_PROCESS = 'process';

    final public const TRANSITION_COMPLETE = 'complete';

    final public const TRANSITION_FAIL = 'fail';

    private function __construct()
    {
    }

    /**
     * @return non-empty-list<string>
     */
    public static function getStates(): array
    {
        return [
            CatalogPromotionUpdateInterface::STATE_PENDING,
            CatalogPromotionUpdateInterface::STATE_PROCESSING,
            CatalogPromotionUpdateInterface::STATE_COMPLETED,
            CatalogPromotionUpdateInterface::STATE_FAILED,
        ];
    }

    public static function getConfig(): array
    {
        $transitions = [];
        foreach (self::getTransitions() as $transition) {
            $transitions[$transition->getName()] = [
                'from' => $transition->getFroms(),
                'to' => $transition->getTos(),
            ];
        }

        return [
            self::NAME => [
                'type' => 'state_machine',
                'marking_store' => [
                    'type' => 'method',
                    'property' => self::PROPERTY_NAME,
                ],
                'supports' => CatalogPromotionUpdateInterface::class,
                'initial_marking' => CatalogPromotionUpdateInterface::STATE_PENDING,
                'places' => self::getStates(),
                'transitions' => $transitions,
            ],
        ];
    }

    /**
     * @return non-empty-list<Transition>
     */
    public static function getTransitions(): array
    {
        return [
            new Transition(
                self::TRANSITION_PROCESS,
                CatalogPromotionUpdateInterface::STATE_PENDING,
                CatalogPromotionUpdateInterface::STATE_PROCESSING,
            ),
            new Transition(
                self::TRANSITION_COMPLETE,
                CatalogPromotionUpdateInterface::STATE_PROCESSING,
                CatalogPromotionUpdateInterface::STATE_COMPLETED,
            ),
            new Transition(
                self::TRANSITION_FAIL,
                [CatalogPromotionUpdateInterface::STATE_PENDING, CatalogPromotionUpdateInterface::STATE_PROCESSING],
                CatalogPromotionUpdateInterface::STATE_FAILED,
            ),
        ];
    }
}
