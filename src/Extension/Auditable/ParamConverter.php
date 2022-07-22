<?php

namespace App\Extension\Auditable;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use App\Extension\Auditable\AuditableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter as ConfigurationParamConverter;

#[AutoconfigureTag('request.param_converter', array(
    'priority' => 10,
))]
class ParamConverter extends DoctrineParamConverter
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        ManagerRegistry $registry,
        array $options = null,
    ) {
        parent::__construct($registry, null, $options ?? array());
    }

    /**
     * @return bool
     */
    public function supports(ConfigurationParamConverter $configuration)
    {
        return is_subclass_of($configuration->getClass(), AuditableInterface::class);
    }

    /**
     * @return bool
     */
    public function apply(Request $request, ConfigurationParamConverter $configuration)
    {
        $destroy = $request->attributes->get('_destroy');
        $restore = $request->attributes->get('_restore');

        if (!$destroy && !$restore) {
            return false;
        }

        $role = match (true) {
            $restore => 'ROLE_RESTORE',
            $destroy => 'ROLE_DESTROY',
            default => $restore ?? $destroy,
        };

        if (!$this->security->isGranted($role)) {
            throw new AccessDeniedHttpException();
        }

        $filter = 'auditable';
        $filters = $this->em->getFilters();
        $enabled = $filters->isEnabled($filter);

        $enabled && $filters->disable($filter);
        $result = parent::apply($request, $configuration);
        $enabled && $filters->enable($filter);

        return $result;
    }
}
