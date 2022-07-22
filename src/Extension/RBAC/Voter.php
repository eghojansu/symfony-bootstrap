<?php

namespace App\Extension\RBAC;

use App\Entity\Csuser;
use App\Repository\CspermRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter as BaseVoter;
use Symfony\Component\Security\Core\Security;

#[AutoconfigureTag('security.voter')]
class Voter extends BaseVoter
{
    private $permissions = array();
    private $query;

    public function __construct(
        private EntityManagerInterface $em,
        private CspermRepository $permRepo,
        private Security $security,
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return $this->permissions[$attribute] ?? (
            $this->permissions[$attribute] = (
                !!$this->permRepo->find($attribute)
            )
        );
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->getUser($token);

        return $user instanceof Csuser && $this->isGranted($user, $attribute);
    }

    private function getUser(TokenInterface $token): Csuser|null
    {
        return $token->getUser();
    }

    private function isGranted(Csuser $user, string $permission): bool
    {
        $sql = $this->query ?? ($this->query = $this->query());
        $query = $this->em->getConnection()->prepare($sql);
        $result = $query->executeQuery(array($user->getId(), $permission));

        return !!$result->fetchOne();
    }

    private function query(): string
    {
        return <<<'SQL'
select
e.*
from csuser a
join csuserr b on b.userid = a.userid
join csrole c on c.`role` = b.`role`
join csrolep d on d.`role` = c.`role`
join csperm e on e.perm = d.perm
where a.userid = ? and e.perm = ?
SQL;
    }
}