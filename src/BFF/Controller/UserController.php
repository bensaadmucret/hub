<?php

namespace App\BFF\Controller;

use App\Core\Entity\User;
use App\Core\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends BaseApiController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private UserRepository $userRepository
    ) {
    }

    /**
     * Liste tous les utilisateurs (admin seulement)
     */
    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(100, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        $users = $this->userRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        $total = $this->userRepository->count([]);

        $data = [];
        foreach ($users as $user) {
            $data[] = $this->serializeUser($user);
        }

        return $this->createSuccessResponse([
            'items' => $data,
            'pagination' => [
                'total' => $total,
                'count' => count($data),
                'page' => $page,
                'pages' => ceil($total / $limit),
            ],
        ]);
    }

    /**
     * Crée un nouvel utilisateur
     */
    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->createErrorResponse('Invalid JSON payload', [], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        if (!isset($data['email']) || !is_string($data['email'])) {
            return $this->createErrorResponse('Validation failed', ['email' => 'Email must be a string.'], Response::HTTP_BAD_REQUEST);
        }
        $user->setEmail($data['email']);

        if (isset($data['password'])) {
            $user->setPlainPassword($data['password']);
        }

        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        // Validation
        $errors = $this->validator->validate($user, null, ['create']);

        if (count($errors) > 0) {
            return $this->createErrorResponse(
                'Validation failed',
                $this->formatValidationErrors($errors),
                Response::HTTP_BAD_REQUEST
            );
        }

        // Vérifier si l'email est déjà utilisé
        $email = $user->getEmail();
        if (empty($email)) {
            return $this->createErrorResponse('Validation failed', ['email' => 'Email cannot be empty.'], Response::HTTP_BAD_REQUEST);
        }
        $existingUser = $this->userRepository->findOneByEmail($email);
        if ($existingUser) {
            return $this->createErrorResponse(
                'Email already in use',
                ['email' => 'This email is already registered'],
                Response::HTTP_CONFLICT
            );
        }

        // Hacher le mot de passe
        if ($user->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $user->getPlainPassword()
            );
            $user->setPassword($hashedPassword);
            $user->eraseCredentials();
        }

        // Par défaut, les nouveaux utilisateurs ont le rôle USER
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->createSuccessResponse(
            $this->serializeUser($user),
            'User created successfully',
            Response::HTTP_CREATED
        );
    }

    /**
     * Affiche un utilisateur spécifique
     */
    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        // Vérifier les permissions
        if (!$this->isGranted('ROLE_ADMIN') && $user !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this user\'s data');
        }

        return $this->createSuccessResponse($this->serializeUser($user));
    }

    /**
     * Met à jour un utilisateur
     */
    #[Route('/{id}', name: 'api_users_update', methods: ['PUT', 'PATCH'])]
    public function update(User $user, Request $request): JsonResponse
    {
        // Vérifier les permissions
        if (!$this->isGranted('ROLE_ADMIN') && $user !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot update this user');
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->createErrorResponse('Invalid JSON payload', [], Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour des champs
        if (isset($data['email'])) {
            if (!is_string($data['email'])) {
                return $this->createErrorResponse('Validation failed', ['email' => 'Email must be a string.'], Response::HTTP_BAD_REQUEST);
            }
            $user->setEmail($data['email']);
        }

        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        // Mise à jour du mot de passe (si fourni)
        if (isset($data['password']) && $data['password']) {
            $user->setPlainPassword($data['password']);
        }

        // Validation
        $errors = $this->validator->validate($user, null, ['update']);

        if (count($errors) > 0) {
            return $this->createErrorResponse(
                'Validation failed',
                $this->formatValidationErrors($errors),
                Response::HTTP_BAD_REQUEST
            );
        }

        // Mise à jour du mot de passe si nécessaire
        if ($user->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $user->getPlainPassword()
            );
            $user->setPassword($hashedPassword);
            $user->eraseCredentials();
        }

        $this->entityManager->flush();

        return $this->createSuccessResponse(
            $this->serializeUser($user),
            'User updated successfully'
        );
    }

    /**
     * Supprime un utilisateur (admin seulement)
     */
    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Empêcher l'auto-suppression
        if ($user === $this->getUser()) {
            return $this->createErrorResponse(
                'You cannot delete your own account',
                [],
                Response::HTTP_FORBIDDEN
            );
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->createSuccessResponse(
            null,
            'User deleted successfully'
        );
    }

    /**
     * Sérialise un utilisateur en tableau
     *
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $user->getUpdatedAt() ? $user->getUpdatedAt()->format(\DateTimeInterface::ATOM) : null,
        ];
    }
}
