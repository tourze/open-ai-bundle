<?php

namespace OpenAIBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use OpenAIBundle\Entity\Conversation;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
#[AsRepository(entityClass: Conversation::class)]
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function save(Conversation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Conversation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<Conversation>
     */
    public function findLatestConversations(int $limit = 10): array
    {
        /** @var array<Conversation> */
        return $this->createQueryBuilder('c')
            ->orderBy('c.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Conversation>
     */
    public function findByTitleLike(string $title): array
    {
        /** @var array<Conversation> */
        return $this->createQueryBuilder('c')
            ->andWhere('c.title LIKE :title')
            ->setParameter('title', '%' . $title . '%')
            ->orderBy('c.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
