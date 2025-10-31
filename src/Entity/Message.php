<?php

namespace OpenAIBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Repository\MessageRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'ims_open_ai_message', options: ['comment' => 'AI消息'])]
class Message implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Column(type: Types::STRING, length: 120, unique: true, options: ['comment' => '消息ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $msgId;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'conversation_id', nullable: false, onDelete: 'CASCADE')]
    private ?Conversation $conversation = null;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [RoleEnum::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, length: 20, enumType: RoleEnum::class, options: ['comment' => '角色'])]
    private RoleEnum $role = RoleEnum::user;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '消息内容'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $content;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '推理过程'])]
    private ?string $reasoningContent = null;

    /**
     * @var array<mixed>|null
     */
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '工具调用'])]
    private ?array $toolCalls = null;

    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '工具调用ID'])]
    private ?string $toolCallId = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '使用模型'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $model;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '输入令牌数'])]
    #[Assert\Range(min: 0)]
    private int $promptTokens = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '输出令牌数'])]
    #[Assert\Range(min: 0)]
    private int $completionTokens = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '总令牌数'])]
    #[Assert\Range(min: 0)]
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

    public function setMsgId(string $msgId): void
    {
        $this->msgId = $msgId;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function getRole(): RoleEnum
    {
        return $this->role;
    }

    public function setRole(RoleEnum $role): void
    {
        $this->role = $role;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function appendContent(string $content): void
    {
        $this->content .= $content;
    }

    public function getReasoningContent(): ?string
    {
        return $this->reasoningContent;
    }

    public function setReasoningContent(?string $reasoningContent): void
    {
        $this->reasoningContent = $reasoningContent;
    }

    public function appendReasoningContent(string $content): void
    {
        $this->reasoningContent .= $content;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * @return array<mixed>|null
     */
    public function getToolCalls(): ?array
    {
        return $this->toolCalls;
    }

    /**
     * @param array<mixed>|null $toolCalls
     */
    public function setToolCalls(?array $toolCalls): void
    {
        $this->toolCalls = $toolCalls;
    }

    /**
     * @param array<mixed> $call
     */
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

    public function setToolCallId(?string $toolCallId): void
    {
        $this->toolCallId = $toolCallId;
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function setPromptTokens(int $promptTokens): void
    {
        $this->promptTokens = $promptTokens;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function setCompletionTokens(int $completionTokens): void
    {
        $this->completionTokens = $completionTokens;
    }

    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    public function setTotalTokens(int $totalTokens): void
    {
        $this->totalTokens = $totalTokens;
    }

    /**
     * @return array<string, mixed>
     */
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

    public function setApiKey(?ApiKey $apiKey): void
    {
        $this->apiKey = $apiKey;
    }
}
