<?php

namespace App\Extension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiContext
{
    public function __construct(
        private RouterInterface $router,
        private SerializerInterface $serializer,
    ) {}

    public function json(
        mixed $data,
        int $status = null,
        array $headers = null,
        array $context = null,
    ): JsonResponse {
        return new JsonResponse(
            $this->serializer->serialize($data, 'json', array_merge(array(
                'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
            ), $context ?? array())),
            $status ?? Response::HTTP_OK,
            $headers ?? array(),
            true,
        );
    }

    public function source(
        mixed $items = null,
        string $message = null,
        array $headers = null,
    ): JsonResponse {
        return $this->json(compact('message', 'items'), null, $headers);
    }

    public function data(
        mixed $data = null,
        string $message = null,
        bool $success = true,
        int $code = null,
        array $headers = null,
    ): JsonResponse {
        return $this->json(
            compact('success') + array_filter(compact('message') + array(
                $success ? 'data' : 'errors' => $data,
            )),
            $code ?? ($success ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY),
            $headers,
        );
    }

    public function message(
        string $message = null,
        mixed $data = null,
        bool $success = true,
        int $code = null,
        array $headers = null,
    ): JsonResponse {
        return $this->data($data, $message, $success, $code, $headers);
    }

    public function saved(
        string|array|bool $action = null,
        mixed $data = null,
        array $headers = null,
    ): JsonResponse {
        return $this->done(__FUNCTION__, $action, $data, $headers, true);
    }

    public function removed(
        string|array|bool $action = null,
        mixed $data = null,
        array $headers = null,
    ): JsonResponse {
        return $this->done(__FUNCTION__, $action, $data, $headers, true);
    }

    public function done(
        string $message,
        string|array|bool $action = null,
        mixed $data = null,
        array $headers = null,
        bool $isData = false,
    ): JsonResponse {
        return $this->data(
            ($data ?? array()) + match(true) {
                is_bool($action) => array('refresh' => $action),
                is_string($action) => array('redirect' => $action),
                $action => array('redirect' => $this->router->generate(...$action)),
                default => array(),
            },
            $isData ? 'Data has been ' . $message : $message,
            true,
            null,
            $headers,
        );
    }

    public function formError(
        FormInterface $form,
        string $message = null,
        int $code = null,
        array $headers = null,
    ): JsonResponse {
        return $this->data(
            self::serializeForm($form),
            $message ?? 'Please fix error in forms',
            false,
            $code,
            $headers,
        );
    }

    private static function serializeForm(FormInterface $form): array
    {
        $errors = array();

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = static::serializeForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }
}