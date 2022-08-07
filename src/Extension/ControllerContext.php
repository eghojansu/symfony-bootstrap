<?php

namespace App\Extension;

use Twig\Environment;
use App\Service\Account;
use Doctrine\ORM\EntityRepository;
use App\Extension\Auditable\Filter;
use App\Extension\ORM\Query\Builder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;

final class ControllerContext
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private ApiContext $apiContext,
        private Account $account,
    ) {}

    public function request(): Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    public function generateUrl(string $route, array $parameters = null, int $referenceType = null): string
    {
        return $this->router->generate($route, $parameters ?? array(), $referenceType ?? UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    public function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    public function redirectToRoute(string $route, array $parameters = null, int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    public function addFlash(string $type, mixed $message): self
    {
        try {
            /** @var Session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add($type, $message);

            return $this;
        } catch (SessionNotFoundException $e) {
            throw new \LogicException('You cannot use the addFlash method if sessions are disabled. Enable them in "config/packages/framework.yaml".', 0, $e);
        }
    }

    public function createForm(string $type, mixed $data = null, array $options = null): FormInterface
    {
        return $this->formFactory->create($type, $data, $options ?? array());
    }

    public function renderForm(
        string $view,
        string $type,
        object|array $data = null,
        callable|bool $persist = false,
        string|array|bool $action = null,
        array $parameters = null,
        array $options = null,
        Request $request = null,
        Response $response = null,
        string|bool $activity = null,
    ): Response {
        $req = $request ?? $this->request();
        $res = $response ?? new Response();
        $wantJson = 'json' === $req->getPreferredFormat();
        $form = $this->createForm($type, $data, $options);

        $form->handleRequest($req);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $response = $action;
                $save = $activity ?? ($persist ? 'Create' : 'Save');

                if ($persist) {
                    if (is_callable($persist)) {
                        $persist($data, $this->em, $form, $wantJson, $request);
                    } else {
                        $this->em->persist($data);
                    }
                }

                $this->em->flush();

                if ($save) {
                    $entity = is_object($data) ? get_class($data) : Utils::className($type);
                    $payload = compact('entity');

                    if (is_object($data) && method_exists($data, 'getId')) {
                        $payload['id'] = $data->getId();
                    } elseif (is_array($data)) {
                        $payload['data'] = $data;
                    }

                    if (!$activity) {
                        $save .= ' ' . $entity;
                    }

                    $this->account->record($save, $payload);
                }

                if ($response instanceof Response) {
                    return $response;
                }

                if (!is_array($response)) {
                    $response = array('Data has been saved', $response);
                }

                if ($wantJson) {
                    return $this->apiContext->done(...$response);
                }

                list($message, $url) = $response;

                $this->addFlash('success', $message);

                if (is_array($url)) {
                    return $this->redirectToRoute(...$url);
                }

                return $this->redirect(is_string($url) ? $url : $req->getUri());
            }

            if ($wantJson) {
                return $this->apiContext->formError($form);
            }

            $res->setStatusCode(422);
        }

        return $this->render($view, ($parameters ?? array()) + array(
            'form' => $form->createView(),
        ));
    }

    public function renderView(string $view, array $parameters = null): string
    {
        return $this->twig->render($this->viewFile($view), $parameters ?? array());
    }

    public function render(string $view, array $parameters = null, Response $response = null): Response
    {
        return ($response ?? new Response())->setContent($this->renderView($view, $parameters));
    }

    public function paginate(
        string $entity,
        array|bool $searchable = null,
        array $filters = null,
        \Closure $modify = null,
        Request $request = null,
        int $minPageSize = 10,
        int $maxPageSize = 100,
    ): Pagination {
        $dataTable = !!($searchable ?? true);
        $req = $request ?? $this->requestStack->getCurrentRequest();
        $trash = $req->query->getBoolean('trash');
        $filtered = null;
        $argPos = 0;

        if ($dataTable) {
            $page = 0;
            $size = min($maxPageSize, max($minPageSize, $req->query->getInt('length')));
            $offset = max(0, $req->query->getInt('start'));
        } else {
            $page = max(1, $req->query->getInt('page'));
            $size = min($maxPageSize, max($minPageSize, $req->query->getInt('size')));
            $offset = ($page - 1) * $size;
        }

        /** @var EntityRepository */
        $repo = $this->em->getRepository($entity);
        $qb = $repo->createQueryBuilder('a')->orderBy('a.id');
        $builder = new Builder($qb);

        if (
            $trash
            && is_subclass_of($entity, AuditableInterface::class)
            && (
                $this->security->isGranted('ROLE_RESTORE')
                || $this->security->isGranted('ROLE_DESTROY')
            )
        ) {
            $filtered = $this->em->getFilters()->isEnabled(Filter::NAME);
            $filtered && $this->em->getFilters()->disable(Filter::NAME);

            $qb->andWhere($qb->expr()->isNotNull('a.deletedAt'));
        }

        if ($where = $builder->applyDataTables($searchable ?? true, $request)) {
            $qb->andWhere($where);
        }

        if ($where = $builder->createArrayFilters($filters)) {
            $qb->andWhere($where);
        }

        if ($modify) {
            $modify($qb, $req, $argPos);
        }

        $qb->setFirstResult($offset)->setMaxResults($size);

        return new Pagination(
            new Paginator($qb),
            $page,
            $size,
            $dataTable,
            $req->query->getInt('draw'),
        );
    }

    protected function viewFile(string $path): string
    {
        if (str_ends_with($path, '.twig')) {
            return $path;
        }

        return str_replace('.', '/', $path) . '.html.twig';
    }
}