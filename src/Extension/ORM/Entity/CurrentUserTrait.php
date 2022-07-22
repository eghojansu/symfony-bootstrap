<?php

namespace App\Extension\ORM\Entity;

use App\Validator as CustomAssert;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

trait CurrentUserTrait
{
    #[Assert\NotBlank(groups: array('current_user'))]
    #[CustomAssert\UserPassword(groups: array('current_user'))]
    #[Ignore]
    private $currentPassword;

    public function getCurrentPassword(): string|null
    {
        return $this->currentPassword;
    }

    public function setCurrentPassword(string|null $currentPassword): static
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }
}