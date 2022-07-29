<?php

namespace App\Security;

use App\Entity\Csuser;
use App\Extension\ControllerContext;
use App\Repository\CsuserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private CsuserRepository $repo,
        private EntityManagerInterface $em,
        private ControllerContext $context,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->repo->findUser($identifier);

        if (!$user) {
            throw new UserNotFoundException();
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('This account is not active');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof Csuser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return Csuser::class === $class || is_subclass_of($class, Csuser::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        /** @var Csuser */
        $that = $user;
        $that->setPassword($newHashedPassword);

        $this->em->flush();
    }
}