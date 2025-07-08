<?php

namespace App\SuperAdmin\Controller;

use App\Core\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/super-admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->getUser();

        if (!$user instanceof User) {
            // This check is mainly for static analysis, as denyAccessUnlessGranted should prevent this.
            throw $this->createAccessDeniedException('This action requires an authenticated user.');
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $totalUsers = $userRepository->count([]);

        return $this->json([
            'status' => 'success',
            'stats' => [
                'total_users' => $totalUsers,
            ],
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/users', name: 'admin_users_list', methods: ['GET'])]
    public function listUsers(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $offset = ($page - 1) * $limit;

        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'isActive' => $user->isActive(),
                'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return $this->json([
            'status' => 'success',
            'data' => $result,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($result),
            ],
        ]);
    }
}
