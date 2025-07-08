<?php

namespace App\BFF\Controller;

use App\Core\Service\PayloadClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/payload')]
class PayloadProxyController extends AbstractController
{
    public function __construct(
        private PayloadClient $payloadClient
    ) {
    }

    #[Route('/{resource}', name: 'bff_payload_list', methods: ['GET'])]
    public function list(string $resource, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $queryParams = $request->query->all();
        $jwt = $request->headers->get('Authorization');

        try {
            $response = $this->payloadClient->request('GET', $resource, $queryParams, $jwt);

            return $this->json([
                'status' => 'success',
                'data' => $response['docs'] ?? $response,
                'pagination' => [
                    'page' => $response['page'] ?? 1,
                    'limit' => $response['limit'] ?? 10,
                    'total' => $response['totalDocs'] ?? (is_array($response['docs'] ?? null) ? count($response['docs']) : 0),
                    'totalPages' => $response['totalPages'] ?? 1,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 500);
        }
    }

    #[Route('/{resource}/{id}', name: 'bff_payload_show', methods: ['GET'])]
    public function show(string $resource, string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $jwt = $request->headers->get('Authorization');

        try {
            $response = $this->payloadClient->request('GET', "$resource/$id", [], $jwt);

            return $this->json([
                'status' => 'success',
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 500);
        }
    }

    #[Route('/{resource}', name: 'bff_payload_create', methods: ['POST'])]
    public function create(string $resource, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true) ?? [];
        if (!is_array($data)) {
            $data = [];
        }
        $jwt = $request->headers->get('Authorization');

        try {
            $response = $this->payloadClient->request('POST', $resource, $data, $jwt);

            return $this->json([
                'status' => 'success',
                'data' => $response,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 500);
        }
    }

    #[Route('/{resource}/{id}', name: 'bff_payload_update', methods: ['PATCH'])]
    public function update(string $resource, string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true) ?? [];
        if (!is_array($data)) {
            $data = [];
        }
        $jwt = $request->headers->get('Authorization');

        try {
            $response = $this->payloadClient->request('PATCH', "$resource/$id", $data, $jwt);

            return $this->json([
                'status' => 'success',
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 500);
        }
    }

    #[Route('/{resource}/{id}', name: 'bff_payload_delete', methods: ['DELETE'])]
    public function delete(string $resource, string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $jwt = $request->headers->get('Authorization');

        try {
            $response = $this->payloadClient->request('DELETE', "$resource/$id", [], $jwt);

            return $this->json([
                'status' => 'success',
                'message' => 'Resource deleted successfully',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 500);
        }
    }
}
