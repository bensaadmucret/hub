<?php

namespace App\BFF\Controller;

use App\Core\Entity\User;
use App\Core\Service\EmailVerificationService;
use App\Core\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation as Nelmio;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager,
        private EmailVerificationService $emailVerificationService,
        private PasswordResetService $passwordResetService,
        private MailerInterface $mailer,
        private string $appEnv,
        private string $defaultFromEmail,
        private UrlGeneratorInterface $urlGenerator,
        private int $jwtTokenTtl,
        private int $jwtRefreshTokenTtl
    ) {
    }

    /**
     * Authentifie un utilisateur et retourne un token JWT
     *
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Authentifie un utilisateur",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="refresh_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="token_ttl", type="integer", example=3600),
     *             @OA\Property(property="refresh_token_ttl", type="integer", example=2592000),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example=["ROLE_USER"]),
     *                 @OA\Property(property="firstName", type="string", nullable=true, example="John"),
     *                 @OA\Property(property="lastName", type="string", nullable=true, example="Doe"),
     *                 @OA\Property(property="isActive", type="boolean", example=true),
     *                 @OA\Property(property="isEmailVerified", type="boolean", example=true),
     *                 @OA\Property(property="lastLogin", type="string", format="date-time", nullable=true, example="2023-06-17T14:25:36+00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Email and password are required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Compte désactivé ou email non vérifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Account is not active or email not verified"),
     *             @OA\Property(property="code", type="string", example="ACCOUNT_INACTIVE_OR_EMAIL_NOT_VERIFIED")
     *         )
     *     )
     * )
     */
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(UserInterface $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid user type',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier si l'utilisateur est actif
        if (!$user->isActive()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Account is not active',
            ], Response::HTTP_FORBIDDEN);
        }

        // Vérifier si l'email est vérifié
        if (!$user->isEmailVerified()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email not verified',
                'code' => 'EMAIL_NOT_VERIFIED',
            ], Response::HTTP_FORBIDDEN);
        }

        // Mettre à jour la date de dernière connexion
        $user->setLastLogin(new \DateTimeImmutable());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Créer un token JWT avec la durée de vie configurée
        $token = $this->jwtManager->create($user);

        // Créer un refresh token avec une durée de vie plus longue
        $refreshToken = $this->jwtManager->create(
            $user,
            [
                'exp' => time() + $this->getParameter('jwt_refresh_token_ttl'),
                'refresh_token' => true,  // Marque ce token comme un refresh token
            ]
        );

        // Retourner la réponse avec le token et les informations utilisateur
        return $this->json([
            'status' => 'success',
            'token' => $token,
            'refresh_token' => $refreshToken,
            'token_ttl' => $this->getParameter('jwt_token_ttl'),
            'refresh_token_ttl' => $this->getParameter('jwt_refresh_token_ttl'),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'isActive' => $user->isActive(),
                'isEmailVerified' => $user->isEmailVerified(),
                'lastLogin' => $user->getLastLogin() ? $user->getLastLogin()->format(\DateTimeInterface::ATOM) : null,
            ],
        ]);
    }

    /**
     * Rafraîchit le token JWT
     *
     * @OA\Post(
     *     path="/api/auth/token/refresh",
     *     summary="Rafraîchit le token JWT",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="refresh_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token de rafraîchissement manquant",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="A refresh token is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token de rafraîchissement invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Invalid or expired refresh token")
     *         )
     *     )
     * )
     *
     * Cette méthode est gérée automatiquement par le bundle LexikJWTAuthenticationBundle
     * via la configuration du firewall. Elle est documentée ici pour référence.
     */
    #[Route('/token/refresh', name: 'api_auth_refresh_token', methods: ['POST'])]
    public function refreshToken(): void
    {
        // Cette méthode ne sera jamais appelée directement grâce à la configuration du firewall
        // Le rafraîchissement du token est géré automatiquement par le bundle
        throw new \LogicException('This method should not be called directly.');
    }

    /**
     * Enregistre un nouvel utilisateur
     *
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Enregistre un nouvel utilisateur",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password", "firstName", "lastName"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePassword123!"),
     *             @OA\Property(property="firstName", type="string", example="John"),
     *             @OA\Property(property="lastName", type="string", example="Doe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur enregistré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User registered successfully. Please check your email to verify your account."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *                 @OA\Property(property="firstName", type="string", example="John"),
     *                 @OA\Property(property="lastName", type="string", example="Doe")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"email": "This value is not a valid email address."})
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Email déjà utilisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Email is already used")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérifier si l'email existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email'] ?? '']);
        if ($existingUser) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email already exists',
            ], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setPlainPassword($data['password'] ?? '');
        $user->setFirstName($data['firstName'] ?? null);
        $user->setLastName($data['lastName'] ?? null);
        $user->setIsActive(false); // L'utilisateur doit d'abord vérifier son email

        // Valider l'entité
        $errors = $this->validator->validate($user, null, ['create']);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPlainPassword()
        );
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();

        // Générer un token de vérification d'email
        $verificationToken = $this->emailVerificationService->generateToken();
        $user->setEmailVerificationToken($verificationToken);

        // Enregistrer l'utilisateur
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoyer l'email de vérification
        try {
            $verificationUrl = $this->urlGenerator->generate('api_auth_verify_email', [
                'token' => $verificationToken,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);

            return $this->json([
                'status' => 'success',
                'message' => 'User registered successfully. Please check your email to verify your account.',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'isEmailVerified' => false,
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // En cas d'erreur d'envoi d'email, on peut choisir de supprimer l'utilisateur
            // ou de lui permettre de redemander l'email de vérification
            // Pour l'instant, on retourne juste une erreur

            return $this->json([
                'status' => 'error',
                'message' => 'User registered but failed to send verification email. Please try to login to request a new verification email.',
            ], Response::HTTP_CREATED);
        }
    }

    /**
     * Vérifie l'email d'un utilisateur avec un token de vérification
     *
     * @OA\Get(
     *     path="/api/auth/verify-email/{token}",
     *     summary="Vérifie l'email d'un utilisateur avec un token de vérification",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token de vérification reçu par email",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email vérifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Email verified successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid or expired verification token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     *
     * @param string $token Le token de vérification
     * @return JsonResponse
     */
    #[Route('/verify-email/{token}', name: 'api_auth_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'emailVerificationToken' => $token,
        ]);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid or expired verification token',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si le token a expiré (optionnel, selon votre implémentation)
        // Ici, nous supposons que le token n'a pas de date d'expiration

        // Activer le compte utilisateur
        $user->setIsEmailVerified(true);
        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $user->setIsActive(true);
        $user->setEmailVerificationToken(null); // Invalider le token après utilisation

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Email verified successfully. Your account is now active.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'isEmailVerified' => true,
                'isActive' => true,
            ],
        ]);
    }

    /**
     * Renvoie un email de vérification
     *
     * @OA\Post(
     *     path="/api/auth/resend-verification",
     *     summary="Renvoie un email de vérification",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email de vérification envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Verification email sent. Please check your inbox.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Email is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Email déjà vérifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Email is already verified")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/resend-verification', name: 'api_auth_resend_verification', methods: ['POST'])]
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        if (empty($email)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($user->isEmailVerified()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is already verified',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Générer un nouveau token de vérification
        $verificationToken = $this->emailVerificationService->generateToken();
        $user->setEmailVerificationToken($verificationToken);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoyer le nouvel email de vérification
        try {
            $verificationUrl = $this->urlGenerator->generate('api_auth_verify_email', [
                'token' => $verificationToken,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);

            return $this->json([
                'status' => 'success',
                'message' => 'Verification email sent successfully',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to send verification email. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Demande de réinitialisation de mot de passe
     *
     * @OA\Post(
     *     path="/api/auth/request-password-reset",
     *     summary="Demande de réinitialisation de mot de passe",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email de réinitialisation envoyé avec succès (même si l'email n'existe pas, pour des raisons de sécurité)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="If your email address exists in our database, you will receive a password reset link at your email address in a few minutes.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Email manquant ou invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Email is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur lors de l'envoi de l'email",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unable to send password reset email. Please try again later.")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/request-password-reset', name: 'api_auth_request_password_reset', methods: ['POST'])]
    public function requestPasswordReset(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        if (empty($email)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        // Pour des raisons de sécurité, on ne révèle pas si l'email existe ou non
        if (!$user) {
            // On retourne un succès même si l'email n'existe pas pour éviter l'énumération
            return $this->json([
                'status' => 'success',
                'message' => 'If your email address exists in our database, you will receive a password reset link at your email address in a few minutes.',
            ]);
        }

        // Générer un token de réinitialisation
        $resetToken = $this->passwordResetService->generateResetToken();
        $expiresAt = new \DateTimeImmutable('+1 hour'); // Lien valable 1 heure

        $user->setResetToken($resetToken);
        $user->setResetTokenExpiresAt($expiresAt);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoyer l'email de réinitialisation
        try {
            // Le service gère lui-même la génération de l'URL de réinitialisation
            $this->passwordResetService->requestPasswordReset($user->getEmail());

            return $this->json([
                'status' => 'success',
                'message' => 'If your email address exists in our database, you will receive a password reset link at your email address in a few minutes.',
            ]);
        } catch (\Exception $e) {
            // En cas d'erreur, on peut logger l'erreur mais on ne le révèle pas à l'utilisateur

            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred while sending the password reset email. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Réinitialise le mot de passe avec un token valide
     *
     * @OA\Post(
     *     path="/api/auth/reset-password",
     *     summary="Réinitialise le mot de passe avec un token valide",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "password"},
     *             @OA\Property(property="token", type="string", description="Token de réinitialisation reçu par email", example="a1b2c3d4e5f6g7h8i9j0"),
     *             @OA\Property(property="password", type="string", format="password", description="Nouveau mot de passe", example="NouveauMotDePasse123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe réinitialisé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Password has been reset successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données manquantes ou invalides",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Token and new password are required")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="The password reset link has expired. Please request a new one.")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Validation failed"),
     *                     @OA\Property(property="errors", type="object", example={"password": "This value is too short. It should have 8 characters or more."})
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Token invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid or expired reset token")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? '';
        $newPassword = $data['password'] ?? '';

        if (empty($token) || empty($newPassword)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Token and new password are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'resetToken' => $token,
        ]);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid or expired reset token',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si le token a expiré
        $now = new \DateTimeImmutable();
        if ($user->getResetTokenExpiresAt() < $now) {
            return $this->json([
                'status' => 'error',
                'message' => 'The password reset link has expired. Please request a new one.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le nouveau mot de passe
        $user->setPlainPassword($newPassword);
        $errors = $this->validator->validate($user, null, ['password']);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();

        // Invalider le token de réinitialisation
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Password has been reset successfully. You can now log in with your new password.',
        ]);
    }

    /**
     * Vérifie si un token de réinitialisation de mot de passe est valide
     *
     * @OA\Get(
     *     path="/api/auth/validate-reset-token/{token}",
     *     summary="Vérifie si un token de réinitialisation de mot de passe est valide",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token de réinitialisation à valider",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="État de validité du token",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="success"),
     *                     @OA\Property(property="valid", type="boolean", example=true),
     *                     @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Invalid or expired reset token"),
     *                     @OA\Property(property="valid", type="boolean", example=false)
     *                 }
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token manquant",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Token is required")
     *         )
     *     )
     * )
     *
     * @param string $token Le token à valider
     * @return JsonResponse
     */
    #[Route('/validate-reset-token/{token}', name: 'api_auth_validate_reset_token', methods: ['GET'])]
    public function validateResetToken(string $token): JsonResponse
    {
        if (empty($token)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Token is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'resetToken' => $token,
        ]);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid or expired reset token',
                'valid' => false,
            ]);
        }

        // Vérifier si le token a expiré
        $now = new \DateTimeImmutable();
        if ($user->getResetTokenExpiresAt() < $now) {
            return $this->json([
                'status' => 'error',
                'message' => 'The password reset link has expired',
                'valid' => false,
            ]);
        }

        return $this->json([
            'status' => 'success',
            'message' => 'Valid reset token',
            'valid' => true,
            'email' => $user->getEmail(),
        ]);
    }

    /**
     * Récupère les informations de l'utilisateur connecté
     */
    /**
     * Déconnecte l'utilisateur actuel
     *
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Déconnecte l'utilisateur actuel",
     *     tags={"Authentication"},
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="JWT Token not found")
     *         )
     *     )
     * )
     */
    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Le système de déconnexion est géré par le firewall
        // Cette méthode ne sera jamais appelée directement grâce à la configuration du firewall
        // Mais nous la laissons pour la documentation et la cohérence de l'API

        return $this->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Récupère les informations de l'utilisateur connecté
     *
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Récupère les informations de l'utilisateur connecté",
     *     tags={"Authentication"},
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), example=["ROLE_USER"]),
     *             @OA\Property(property="firstName", type="string", nullable=true, example="John"),
     *             @OA\Property(property="lastName", type="string", nullable=true, example="Doe"),
     *             @OA\Property(property="isActive", type="boolean", example=true),
     *             @OA\Property(property="isEmailVerified", type="boolean", example=true),
     *             @OA\Property(property="createdAt", type="string", format="date-time", example="2023-06-17T14:25:36+00:00"),
     *             @OA\Property(property="lastLogin", type="string", format="date-time", nullable=true, example="2023-06-17T14:25:36+00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="JWT Token not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'isActive' => $user->isActive(),
            'isEmailVerified' => $user->isEmailVerified(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'lastLogin' => $user->getLastLogin() ? $user->getLastLogin()->format(\DateTimeInterface::ATOM) : null,
        ]);
    }
}
