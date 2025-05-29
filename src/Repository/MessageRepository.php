<?php

namespace OpenAIBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

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
            ->getSingleResult();

        return [
            'prompt_tokens' => (int) $result['prompt_tokens'],
            'completion_tokens' => (int) $result['completion_tokens'],
            'total_tokens' => (int) $result['total_tokens'],
        ];
    }

    public function findByRole(Conversation $conversation, string $role): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.role = :role')
            ->setParameter('conversation', $conversation)
            ->setParameter('role', $role)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithToolCalls(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.toolCalls IS NOT NULL')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByToolCallId(string $toolCallId): ?Message
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.toolCallId = :toolCallId')
            ->setParameter('toolCallId', $toolCallId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
