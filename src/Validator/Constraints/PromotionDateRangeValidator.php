<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Validator\Constraints;

use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class PromotionDateRangeValidator extends ConstraintValidator
{
    /**
     * @param PromotionInterface|mixed $value
     * @param PromotionDateRange|Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!$constraint instanceof PromotionDateRange) {
            throw new UnexpectedTypeException($constraint, PromotionDateRange::class);
        }

        if (!$value instanceof PromotionInterface) {
            throw new UnexpectedValueException($value, PromotionInterface::class);
        }

        $startsAt = $value->getStartsAt();
        $endsAt = $value->getEndsAt();

        if (null === $startsAt || null === $endsAt) {
            return;
        }

        if ($startsAt > $endsAt) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('endsAt')
                ->addViolation()
            ;
        }
    }
}
