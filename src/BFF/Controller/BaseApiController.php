<?php

namespace App\BFF\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class BaseApiController extends AbstractController
{
    /**
     * Crée une réponse JSON standardisée pour une requête réussie
     */
    protected function createSuccessResponse(
        $data = null,
        ?string $message = null,
        int $statusCode = Response::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        $response = ['status' => 'success'];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->json($response, $statusCode, $headers);
    }

    /**
     * Crée une réponse d'erreur standardisée
     */
    protected function createErrorResponse(
        string $message,
        array $errors = [],
        int $statusCode = Response::HTTP_BAD_REQUEST,
        array $headers = []
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $this->json($response, $statusCode, $headers);
    }

    /**
     * Formate les erreurs de validation
     */
    protected function formatValidationErrors(ConstraintViolationListInterface $errors): array
    {
        $formattedErrors = [];

        foreach ($errors as $error) {
            $formattedErrors[$error->getPropertyPath()] = $error->getMessage();
        }

        return $formattedErrors;
    }

    /**
     * Formate les erreurs de formulaire
     */
    protected function getFormErrors(FormInterface $form): array
    {
        $errors = [];

        // Récupère les erreurs globales du formulaire
        foreach ($form->getErrors() as $error) {
            $errors['_form'][] = $error->getMessage();
        }

        // Récupère les erreurs pour chaque champ
        foreach ($form as $child) {
            if (!$child->isValid()) {
                foreach ($child->getErrors() as $error) {
                    $errors[$child->getName()][] = $error->getMessage();
                }
            }
        }

        return $errors;
    }

    /**
     * Vérifie si l'utilisateur a un certain rôle
     */
    protected function hasRole(string $role): bool
    {
        return $this->isGranted('ROLE_SUPER_ADMIN') || $this->isGranted($role);
    }

    /**
     * Vérifie si l'utilisateur est propriétaire de la ressource
     */
    protected function isOwner($entity, string $ownerProperty = 'user'): bool
    {
        $user = $this->getUser();

        if (!method_exists($entity, 'get' . ucfirst($ownerProperty))) {
            return false;
        }

        $owner = $entity->{'get' . ucfirst($ownerProperty)}();

        return $owner && $user && $owner->getId() === $user->getId();
    }

    /**
     * Vérifie les permissions et renvoie une réponse d'erreur si nécessaire
     */
    protected function checkPermission(
        $entity,
        string $ownerProperty = 'user',
        array $allowedRoles = ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']
    ): ?JsonResponse {
        $user = $this->getUser();

        // Vérifie si l'utilisateur est connecté
        if (!$user) {
            return $this->createErrorResponse(
                'Authentication required',
                [],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifie si l'utilisateur a un des rôles autorisés
        $hasAllowedRole = false;
        foreach ($allowedRoles as $role) {
            if ($this->isGranted($role)) {
                $hasAllowedRole = true;
                break;
            }
        }

        // Si l'utilisateur n'a pas de rôle autorisé et n'est pas propriétaire de la ressource
        if (!$hasAllowedRole && !$this->isOwner($entity, $ownerProperty)) {
            return $this->createErrorResponse(
                'You do not have permission to access this resource',
                [],
                Response::HTTP_FORBIDDEN
            );
        }

        return null;
    }
}
