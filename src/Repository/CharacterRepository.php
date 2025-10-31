<?php

namespace OpenAIBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use OpenAIBundle\Entity\Character;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Character>
 */
#[AsRepository(entityClass: Character::class)]
class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    public function save(Character $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Character $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 获取所有激活的角色
     *
     * @return array<Character>
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.valid = :valid')
            ->setParameter('valid', true)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据名称查找角色
     */
    public function findOneByName(string $name): ?Character
    {
        return $this->createQueryBuilder('c')
            ->where('c.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
