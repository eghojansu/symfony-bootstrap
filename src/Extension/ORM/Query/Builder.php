<?php

namespace App\Extension\ORM\Query;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Comparison;
use Symfony\Component\HttpFoundation\Request;

final class Builder
{
    public function __construct(
        public QueryBuilder $qb,
        public int $argNo = 1,
    ) {}

    public function createArrayFilters(array|null $arrFilters, string|array $prefix = null): Expr|null
    {
        $filters = array();

        if ($arrFilters) {
            array_walk($arrFilters, function ($value, $key) use (&$filters, $prefix) {
                $filters[] = $this->addExpr($key, $value, $prefix);
            });
        }

        return $filters ? $this->qb->expr()->andX(...$filters) : null;
    }

    public function applyDataTables(array|bool|null $search, Request $request, string|array $prefix = null): Expr|null
    {
        if (!$search) {
            return null;
        }

        $filters = array();
        $keyword = $request->get('search');
        $columns = $request->get('columns');
        $orders = $request->get('order');

        if (is_array($search) && ($keyword['value'] ?? null)) {
            array_walk($search, function (string $field) use (&$filters, $keyword, $prefix) {
                $filters[] = $this->addExpr($field, $keyword['value'], $prefix);
            });
        }

        if ($columns) {
            array_walk($columns, function (array $column) use (&$filters, $prefix) {
                if ($column['searchable'] && $column['search']['value']) {
                    $filters[] = $this->addExpr($column['data'], $column['search']['value'], $prefix);
                }
            });
        }

        if ($orders) {
            array_walk($orders, function (array $order) use ($columns, $prefix) {
                $column = $columns[$order['column']] ?? null;

                if ($column && $column['orderable']) {
                    $this->qb->addOrderBy($this->quote($column['data'], $prefix), $order['dir']);
                }
            });
        }

        return $filters ? $this->qb->expr()->andX(...$filters) : null;
    }

    public function addExpr(string $field, $value, string|array $prefix = null): Comparison
    {
        $prop = $field;
        $oprL = null;
        $oprR = '=';

        if (preg_match('/^(?:([^\w]+)\h*)?([\w.]+)(?:\h*([^\w]+))?$/i', $prop, $match, PREG_UNMATCHED_AS_NULL)) {
            $oprL = $match[1] ?? null;
            $oprR = $match[3] ?? null;
            $prop = $match[2];
        }

        $prop = $this->quote($prop, $prefix);

        return match(true) {
            '%' === $oprL, '%' === $oprR => $this->qb
                ->setParameter($this->argNo, self::quoteLike($value, '%' === $oprL, '%' === $oprR))
                ->expr()->like($prop, '?' . $this->argNo++),
            '<>' === $oprR, '!=' === $oprR => $this->qb
                ->setParameter($this->argNo, $value)
                ->expr()->neq($prop, '?' . $this->argNo++),
            default => $this->qb
                ->setParameter($this->argNo, $value)
                ->expr()->eq($prop, '?' . $this->argNo++),
        };
    }

    private function quote(string $field, string|array $prefix = null): string
    {
        if (false === strpos($field, '.')) {
            if (is_string($prefix)) {
                $add = $prefix;
            } elseif (is_array($prefix) && isset($prefix[$field])) {
                $add = $prefix[$field];
            } else {
                $add = $this->qb->getRootAliases()[0];
            }

            return $add . '.' . $field;
        }

        return $field;
    }

    private static function quoteLike(string $value, bool $left, bool $right = false): string
    {
        if (false === strpos($value, '%')) {
            return ($left ? '%' : null) . $value . ($right ? '%' : null);
        }

        return $value;
    }
}