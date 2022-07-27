<?php

namespace App\Extension;

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
        $json = $this->serializer->serialize($data, 'json', array_merge(array(
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ), $context ?? array()));

        return new JsonResponse($json, $status ?? Response::HTTP_OK, $headers ?? array(), true);
    }

    public function source(
        mixed $items = null,
        string $message = null,
        array $headers = null,
    ): JsonResponse {
        $payload = array_filter(compact('message', 'items'));

        return $this->json($payload, null, $headers);
    }

    public function data(
        mixed $data = null,
        string $message = null,
        bool $success = true,
        int $code = null,
        array $headers = null,
    ): JsonResponse {
        $payload = compact('success') + array_filter(compact('message', 'data'));
        $status = $code ?? ($success ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);

        return $this->json($payload, $status, $headers);
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
        $add = array();

        if (is_bool($action)) {
            $add['refresh'] = $action;
        } elseif (is_string($action)) {
            $add['redirect'] = $action;
        } elseif ($action) {
            $add['redirect'] = $this->router->generate(...$action);
        }

        return $this->data(
            ($data ?? array()) + $add,
            $isData ? 'Data has been ' . $message : $message,
            true,
            null,
            $headers,
        );
    }
}