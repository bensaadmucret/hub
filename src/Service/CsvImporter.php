<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Entity\User;
use App\Exception\InvalidCsvRowException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvImporter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function import(string $filePath, int $batchSize = 100): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException(sprintf('Le fichier "%s" n\'existe pas ou est illisible.', $filePath));
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Impossible d\'ouvrir le fichier "%s".', $filePath));
        }

        try {
            $header_raw = fgetcsv($handle, 0, ';');
            if ($header_raw === false) {
                throw new \RuntimeException('Le fichier CSV est vide ou mal formé.');
            }
            /** @var array<string> $header */
            $header = array_map('strval', $header_raw);

            $lineNumber = 1;
            $i = 0;
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $lineNumber++;
                if (count($header) !== count($row)) {
                    continue; // Skip malformed rows
                }
                $data = array_combine($header, $row);
                $this->processRow($data, $lineNumber);

                if (($i % $batchSize) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
                $i++;
            }
            $this->entityManager->flush();
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function processRow(array $data, int $lineNumber): void
    {
        $email = isset($data['email']) && is_string($data['email']) ? trim($data['email']) : null;
        $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : null;
        $firstName = isset($data['firstName']) && is_string($data['firstName']) ? trim($data['firstName']) : null;
        $lastName = isset($data['lastName']) && is_string($data['lastName']) ? trim($data['lastName']) : null;

        if (empty($email) || empty($password)) {
            throw new InvalidCsvRowException(sprintf('L\'email et le mot de passe sont requis à la ligne %d.', $lineNumber));
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $violations = $this->validator->validate($user);
        if ($violations->count() > 0) {
            /** @var array<string, array<string>> $errors */
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = [(string) $violation->getMessage()];
            }
            throw new InvalidCsvRowException(sprintf('Erreur de validation à la ligne %d.', $lineNumber), $errors);
        }

        $this->entityManager->persist($user);
    }
}
