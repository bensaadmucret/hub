<?php

namespace App\Command;

use App\Core\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user account',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'User password')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Set user as admin')
            ->addOption('first-name', 'f', InputOption::VALUE_OPTIONAL, 'User first name', '')
            ->addOption('last-name', 'l', InputOption::VALUE_OPTIONAL, 'User last name', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        if (!is_string($email)) {
            $io->error('Email must be a string.');
            return Command::FAILURE;
        }

        $password = $input->getArgument('password');
        if (!is_string($password)) {
            $io->error('Password must be a string.');
            return Command::FAILURE;
        }

        $isAdmin = (bool) $input->getOption('admin');

        $firstName = $input->getOption('first-name');
        if (!is_string($firstName)) {
            $firstName = '';
        }

        $lastName = $input->getOption('last-name');
        if (!is_string($lastName)) {
            $lastName = '';
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->error(sprintf('User with email %s already exists!', $email));
            return Command::FAILURE;
        }

        // Créer le nouvel utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        // Définir les rôles
        $roles = ['ROLE_USER'];
        if ($isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }
        $user->setRoles($roles);

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Activer et vérifier l'email pour les comptes créés en ligne de commande
        $user->setIsActive(true);
        $user->setIsEmailVerified(true);
        $user->setEmailVerifiedAt(new \DateTimeImmutable());

        // Enregistrer l'utilisateur
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User %s was created successfully!', $email));

        if ($isAdmin) {
            $io->note('This user has admin privileges.');
        }

        return Command::SUCCESS;
    }
}
