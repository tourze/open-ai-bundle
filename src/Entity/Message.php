<?php

namespace OpenAIBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Repository\MessageRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'ims_open_ai_message', options: ['comment' => 'AI消息'])]
class Message implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\Column(type: Types::STRING, length: 120, unique: true, options: ['comment' => '消息ID'])]
    private string $msgId;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'conversation_id', nullable: false, onDelete: 'CASCADE')]
    private ?Conversation $conversation = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: RoleEnum::class, options: ['comment' => '角色'])]
    private RoleEnum $role = RoleEnum::user;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '消息内容'])]
    private string $content;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '推理过程'])]
    private ?string $reasoningContent = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '工具调用'])]
    private ?array $toolCalls = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '工具调用ID'])]
    private ?string $toolCallId = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '使用模型'])]
    private string $model;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '输入令牌数'])]
    private int $promptTokens = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '输出令牌数'])]
    private int $completionTokens = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '总令牌数'])]
    private int $totalTokens = 0;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    private ?ApiKey $apiKey = null;


    public function __toString(): string
    {
        return $this->content;
    }



    public function getMsgId(): string
    {
        return $this->msgId;
    }

    public function setMsgId(string $msgId): self
    {
        $this->msgId = $msgId;

        return $this;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getRole(): RoleEnum
    {
        return $this->role;
    }

    public function setRole(RoleEnum $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function appendContent(string $content): void
    {
        $this->content .= $content;
    }

    public function getReasoningContent(): ?string
    {
        return $this->reasoningContent;
    }

    public function setReasoningContent(?string $reasoningContent): self
    {
        $this->reasoningContent = $reasoningContent;

        return $this;
    }

    public function appendReasoningContent(string $content): void
    {
        $this->reasoningContent .= $content;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getToolCalls(): ?array
    {
        return $this->toolCalls;
    }

    public function setToolCalls(?array $toolCalls): self
    {
        $this->toolCalls = $toolCalls;

        return $this;
    }

    public function addToolCall(array $call): void
    {
        if (null === $this->toolCalls) {
            $this->toolCalls = [];
        }
        $this->toolCalls[] = $call;
    }

    public function getToolCallId(): ?string
    {
        return $this->toolCallId;
    }

    public function setToolCallId(?string $toolCallId): self
    {
        $this->toolCallId = $toolCallId;

        return $this;
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function setPromptTokens(int $promptTokens): self
    {
        $this->promptTokens = $promptTokens;

        return $this;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function setCompletionTokens(int $completionTokens): self
    {
        $this->completionTokens = $completionTokens;

        return $this;
    }

    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    public function setTotalTokens(int $totalTokens): self
    {
        $this->totalTokens = $totalTokens;

        return $this;
    }

    public function toArray(): array
    {
        $result = [
            'role' => $this->role->value,
            'content' => $this->content,
        ];

        if (null !== $this->toolCalls) {
            $result['tool_calls'] = $this->toolCalls;
        }
        if (null !== $this->toolCallId) {
            $result['tool_call_id'] = $this->toolCallId;
        }

        return $result;
    }

    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    public function setApiKey(?ApiKey $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }}
