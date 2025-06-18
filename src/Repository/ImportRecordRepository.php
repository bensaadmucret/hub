<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ImportRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportRecord>
 *
 * @method ImportRecord|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImportRecord|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method ImportRecord[]    findAll()
 * @method ImportRecord[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, ?int $limit = null, ?int $offset = null)
 */
class ImportRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportRecord::class);
    }

    public function save(ImportRecord $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ImportRecord $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function clear(): void
    {
        $this->getEntityManager()->clear();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
