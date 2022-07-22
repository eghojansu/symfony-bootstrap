<?php

namespace App\Service;

use App\Entity\Csrole;
use App\Extension\Utils;
use Doctrine\ORM\QueryBuilder;
use App\Repository\CsroleRepository;
use Symfony\Component\Security\Core\Security;

class Choices
{
    public function __construct(
        private Security $security,
        private CsroleRepository $rolesRepo,
    ) {}

    public function support(string $name): bool
    {
        return method_exists($this, $name) && '_' !== $name[0] && !in_array(
            $name,
            array('support', 'getChoices'),
        );
    }

    public function getChoices(
        string $name,
        string $contains = null,
        string $prefix = null,
    ): array {
        if ($contains && false === strpos($contains, '%')) {
            $contains = '%' . $contains . '%';
        }

        if ($prefix) {
            $prefix = str_replace('%', '', $prefix) . '%';
        }

        return match($name) {
            'roles' => $this->roles($contains, $prefix),
            default => array(),
        };
    }

    public function roles(string $contains = null, string $prefix = null): array
    {
        $qb = $this->rolesRepo->createQueryBuilder('a');

        if ($contains || $prefix) {
            self::applyRowFilter($qb, array(
                array($contains, 'id,description'),
                array($prefix, 'id,description'),
            ));
        }

        $roles = Utils::reduce(
            $qb->getQuery()->getResult(),
            static fn (array $roles, Csrole $role) => $roles + array(
                $role->getId() => $role->getDescription(),
            ),
            array(),
        );

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $roles['Editor'] = 'ROLE_EDITOR';
        }

        if ($this->security->isGranted('ROLE_ROOT')) {
            $roles['Administrator'] = 'ROLE_ADMIN';
            $roles['Root'] = 'ROLE_ROOT';
        }

        $roles['User'] = 'ROLE_USER';

        return $roles;
    }

    private static function applyRowFilter(
        QueryBuilder $qb,
        array $rows,
        string $prefix = 'a',
    ): void {
        $pos = 1;
        $wPrefix = static fn (string $field) => (
            false === strpos($field, '.') ? $prefix . '.' . $field : $field
        );

        $qb->where(
            $qb->expr()->andX(
                ...Utils::map(
                    $rows,
                    static function (array $row) use ($qb, $wPrefix, &$pos) {
                        list($value, $fields, $nullable, $call) = $row + array(
                            2 => true, null
                        );

                        $qb->expr()->orX(
                            ...($nullable ? array($qb->expr()->isNull('?' . $pos)) : array()),
                            ...Utils::map(
                                Utils::split($fields),
                                static function (string $field) use ($qb, $call, $wPrefix, &$pos)  {
                                    match ($call) {
                                        default => $qb->expr()->like($wPrefix($field), '?' . $pos),
                                    };
                                },
                                false,
                            ),
                        );
                        $qb->setParameter($pos++, $value);
                    },
                    false,
                ),
            ),
        );
    }
}