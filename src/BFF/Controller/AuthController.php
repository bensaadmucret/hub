<?php

namespace App\BFF\Controller;

use App\Core\Entity\User;
use App\Core\Service\EmailVerificationService;
use App\Core\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
#[OA\Tag(name: 'Authentication')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailVerificationService $emailVerificationService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly int $jwtTokenTtl,
        private readonly int $jwtRefreshTokenTtl
    ) {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    #[OA\Post(
        summary: 'Authenticates a user and returns a JWT token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'token', type: 'string', example: '...'),
                        new OA\Property(property: 'token_ttl', type: 'integer', example: 3600),
                        new OA\Property(property: 'refresh_token_ttl', type: 'integer', example: 2592000),
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '...'),
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER']),
                            new OA\Property(property: 'firstName', type: 'string', nullable: true, example: 'John'),
                            new OA\Property(property: 'lastName', type: 'string', nullable: true, example: 'Doe'),
                            new OA\Property(property: 'isActive', type: 'boolean', example: true),
                            new OA\Property(property: 'isEmailVerified', type: 'boolean', example: true),
                            new OA\Property(property: 'lastLogin', type: 'string', format: 'date-time', nullable: true, example: '...')
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 403, description: 'Account not active or email not verified')
        ]
    )]
    public function login(UserInterface $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->json(['status' => 'error', 'message' => 'Invalid user type'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isActive() || !$user->isEmailVerified()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Account is not active or email not verified',
                'code' => 'ACCOUNT_INACTIVE_OR_EMAIL_NOT_VERIFIED'
            ], Response::HTTP_FORBIDDEN);
        }

        $user->setLastLogin(new \DateTimeImmutable());
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);

        return $this->json([
            'status' => 'success',
            'token' => $token,
            'token_ttl' => $this->jwtTokenTtl,
            'refresh_token_ttl' => $this->jwtRefreshTokenTtl,
            'user' => $this->serializeUser($user),
        ]);
    }

    #[Route('/token/refresh', name: 'api_auth_refresh_token', methods: ['POST'])]
    public function refreshToken(): void
    {
        // This method is handled by the LexikJWTAuthenticationBundle.
        throw new \LogicException('This method should not be called directly.');
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    #[OA\Post(
        summary: 'Registers a new user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'firstName', 'lastName'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'new.user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePassword123!'),
                    new OA\Property(property: 'firstName', type: 'string', example: 'Jane'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Doe')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'User registered. Please check your email to verify your account.'),
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'email', type: 'string', format: 'email'),
                            new OA\Property(property: 'firstName', type: 'string'),
                            new OA\Property(property: 'lastName', type: 'string')
                        ])
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid data'),
            new OA\Response(response: 409, description: 'Email already in use')
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email'] ?? null]);
        if ($existingUser) {
            return $this->json(['status' => 'error', 'message' => 'Email already exists'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setPlainPassword($data['password'] ?? '');
        $user->setFirstName($data['firstName'] ?? null);
        $user->setLastName($data['lastName'] ?? null);
        $user->setIsActive(false);

        $errors = $this->validator->validate($user, null, ['create']);
        if (count($errors) > 0) {
            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $this->formatValidationErrors($errors),
            ], Response::HTTP_BAD_REQUEST);
        }

        $plainPassword = $user->getPlainPassword();
        if (empty($plainPassword)) {
             return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => ['password' => 'Password cannot be empty.'],
             ], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();

        $verificationToken = $this->emailVerificationService->generateToken();
        $user->setEmailVerificationToken($verificationToken);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        try {
            $verificationUrl = $this->urlGenerator->generate('api_auth_verify_email', ['token' => $verificationToken], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);

            return $this->json([
                'status' => 'success',
                'message' => 'User registered successfully. Please check your email to verify your account.',
                'user' => $this->serializeUser($user, false),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Log the error here
            return $this->json([
                'status' => 'error',
                'message' => 'User registered but failed to send verification email.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/verify-email/{token}', name: 'api_auth_verify_email', methods: ['GET'])]
    #[OA\Get(
        summary: "Verifies a user's email with a token",
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Email verified successfully'),
            new OA\Response(response: 400, description: 'Invalid or expired token'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function verifyEmail(string $token): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            return $this->json(['status' => 'error', 'message' => 'User not found or token is invalid'], Response::HTTP_NOT_FOUND);
        }

        if ($this->emailVerificationService->isTokenExpired($user)) {
             return $this->json(['status' => 'error', 'message' => 'Verification token has expired.'], Response::HTTP_BAD_REQUEST);
        }

        $user->setIsEmailVerified(true);
        $user->setIsActive(true);
        $user->setEmailVerificationToken(null);
        $this->entityManager->flush();

        return $this->json(['status' => 'success', 'message' => 'Email verified successfully.']);
    }

    /**
     * @param iterable<ConstraintViolationInterface> $errors
     * @return array<string, string>
     */
    private function formatValidationErrors(iterable $errors): array
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }
        return $errorMessages;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user, bool $includePrivateData = true): array
    {
        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'isEmailVerified' => $user->isEmailVerified(),
        ];

        if ($includePrivateData) {
            $data['isActive'] = $user->isActive();
            $data['lastLogin'] = $user->getLastLogin() ? $user->getLastLogin()->format(\DateTimeInterface::ATOM) : null;
        }

        return $data;
    }
}
