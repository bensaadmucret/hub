<?php

namespace App\Core\Repository;

use App\Core\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->save($user, true);
    }

    /**
     * @return User|null
     */
    public function findOneByEmail(string $email): ?User
    {
        /** @var User|null $result */
        $result = $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', strtolower($email))
            ->getQuery()
            ->getOneOrNullResult();
        return $result;
    }

    /**
     * @return User|null
     */
    public function findActiveUserByEmail(string $email): ?User
    {
        /** @var User|null $result */
        $result = $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->andWhere('u.isActive = :isActive')
            ->setParameter('email', strtolower($email))
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult();
        return $result;
    }

    /**
     * @return User|null
     */
    public function findOneByResetToken(string $token): ?User
    {
        /** @var User|null $result */
        $result = $this->createQueryBuilder('u')
            ->where('u.resetToken = :token')
            ->andWhere('u.resetTokenExpiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
        return $result;
    }
}
