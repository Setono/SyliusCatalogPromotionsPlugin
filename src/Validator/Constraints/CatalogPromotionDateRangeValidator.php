<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Validator\Constraints;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class CatalogPromotionDateRangeValidator extends ConstraintValidator
{
    /**
     * @param CatalogPromotionInterface|mixed $value
     * @param CatalogPromotionDateRange|Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!$constraint instanceof CatalogPromotionDateRange) {
            throw new UnexpectedTypeException($constraint, CatalogPromotionDateRange::class);
        }

        if (!$value instanceof CatalogPromotionInterface) {
            throw new UnexpectedValueException($value, CatalogPromotionInterface::class);
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
