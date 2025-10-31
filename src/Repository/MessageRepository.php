<?php

namespace OpenAIBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Message>
 */
#[AsRepository(entityClass: Message::class)]
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<Message>
     */
    public function findByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array{prompt_tokens: int, completion_tokens: int, total_tokens: int}
     */
    public function getConversationTokenCounts(Conversation $conversation): array
    {
        $result = $this->createQueryBuilder('m')
            ->select(
                'SUM(m.promptTokens) as prompt_tokens',
                'SUM(m.completionTokens) as completion_tokens',
                'SUM(m.totalTokens) as total_tokens'
            )
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->getQuery()
            ->getSingleResult()
        ;

        return [
            'prompt_tokens' => (int) $result['prompt_tokens'],
            'completion_tokens' => (int) $result['completion_tokens'],
            'total_tokens' => (int) $result['total_tokens'],
        ];
    }

    /**
     * @return array<Message>
     */
    public function findByRole(Conversation $conversation, string $role): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.role = :role')
            ->setParameter('conversation', $conversation)
            ->setParameter('role', $role)
            ->orderBy('m.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Message>
     */
    public function findWithToolCalls(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.toolCalls IS NOT NULL')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByToolCallId(string $toolCallId): ?Message
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.toolCallId = :toolCallId')
            ->setParameter('toolCallId', $toolCallId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
