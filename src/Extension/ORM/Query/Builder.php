<?php

namespace App\Extension\ORM\Query;

use App\Extension\Utils;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Comparison;
use Symfony\Component\HttpFoundation\Request;

final class Builder
{
    public function __construct(
        private QueryBuilder $qb,
        private int $argNo = 1,
    ) {}

    public function createArrayFilters(array|null $arrFilters): Expr|null
    {
        $filters = Utils::map(
            $arrFilters ?? array(),
            function ($value, $key) {
                list($prop, $opr) = explode(' ', $key) + array(1 => null);

                if (false === strpos($prop, '.')) {
                    $prop = 'a.' . $prop;
                }

                return $this->addExpr($prop, $value, $opr);
            },
            false,
        );

        return $filters ? $this->qb->expr()->andX(...$filters) : null;
    }

    public function createDataTableFilters(array|bool $search, Request $request): Expr|null
    {
        $filters = array();
        $keyword = $request->get('search');

        if ($search && is_array($search) && ($keyword['value'] ?? null)) {
            array_walk($search, static function (string $operation, string|int $field) use ($keyword) {
                if (is_numeric($field)) {
                    $field = $operation;
                    $operation = '=';
                }
            });
        }

        return $filters ? $this->qb->expr()->andX(...$filters) : null;
    }

    public function addExpr(string $prop, $value, string $operation = null): Comparison
    {
        return match($operation) {
            '<>', '!=' => $this->qb
                ->setParameter($this->argNo, $value)
                ->expr()->neq($prop, '?' . $this->argNo++),
            default => $this->qb
                ->setParameter($this->argNo, $value)
                ->expr()->eq($prop, '?' . $this->argNo++),
        };
    }
}