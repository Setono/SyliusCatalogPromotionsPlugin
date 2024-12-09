<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Rule;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use RuntimeException;
use function sprintf;
use Webmozart\Assert\Assert;

abstract class Rule implements RuleInterface
{
    private static int $aliasIndex = 0;

    private static int $parameterIndex = 0;

    protected function getRootAlias(QueryBuilder $queryBuilder): string
    {
        $rootAliases = $queryBuilder->getRootAliases();

        if (count($rootAliases) === 0) {
            throw new RuntimeException('No root aliases');
        }

        return $rootAliases[0];
    }

    /**
     * @return string Generated or existing alias
     */
    protected function join(QueryBuilder $queryBuilder, string $join, string $aliasPrefix): string
    {
        /** @var array<string, list<Join>> $existingJoins */
        $existingJoins = $queryBuilder->getDQLPart('join');

        $rootAlias = $this->getRootAlias($queryBuilder);
        Assert::keyExists($existingJoins, $rootAlias);

        foreach ($existingJoins[$rootAlias] as $existingJoin) {
            if ($existingJoin->getJoin() === $join) {
                return (string) $existingJoin->getAlias();
            }
        }

        $alias = self::generateAlias($aliasPrefix);
        $queryBuilder->join($join, $alias);

        return $alias;
    }

    protected static function getConfigurationValue(string $key, array $configuration, bool $optional = false): mixed
    {
        if (!$optional && !array_key_exists($key, $configuration)) {
            throw new InvalidArgumentException(sprintf('The key "%s" does not exist in the configuration', $key));
        }

        return $configuration[$key] ?? null;
    }

    protected static function generateAlias(string $prefix): string
    {
        $alias = $prefix . self::$aliasIndex;

        ++self::$aliasIndex;

        return $alias;
    }

    protected static function generateParameter(string $prefix): string
    {
        $parameter = $prefix . self::$parameterIndex;

        ++self::$parameterIndex;

        return $parameter;
    }
}
