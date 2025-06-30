<?php

namespace OpenAIBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenAIBundle\Repository\CharacterRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;

#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: 'ims_open_ai_character', options: ['comment' => 'AI角色设定'])]
class Character implements \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '角色名称'])]
    private string $name;

    #[Groups(groups: ['restful_read'])]
    #[TrackColumn]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '头像'])]
    private ?string $avatar = null;

    #[Groups(groups: ['restful_read'])]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '系统提示词'])]
    private string $systemPrompt;

    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '温度参数'])]
    private float $temperature = 1;

    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '采样概率阈值'])]
    private float $topP = 0.7;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '最大生成令牌数'])]
    private int $maxTokens = 2000;

    /**
     * @var float 避免重复主题。果值为正，会根据新 token 到目前为止是否出现在文本中对其进行惩罚，从而增加模型谈论新主题的可能性。取值范围为 [-2.0, 2.0]。
     */
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '存在惩罚'])]
    private float $presencePenalty = 0.0;

    /**
     * @var float 避免重复用词。如果值为正，会根据新 token 在文本中的出现频率对其进行惩罚，从而降低模型逐字重复的可能性。取值范围为 [-2.0, 2.0]。
     */
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '频率惩罚'])]
    private float $frequencyPenalty = 0.0;

    #[ORM\ManyToOne(targetEntity: ApiKey::class)]
    #[ORM\JoinColumn(name: 'preferred_api_key_id', nullable: true, onDelete: 'SET NULL')]
    private ?ApiKey $preferredApiKey = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '支持函数'])]
    private ?array $supportFunctions = [];

    /**
     * @var Collection<int, Conversation>
     */
    #[Ignore]
    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'actor')]
    private Collection $conversations;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    public function __construct()
    {
        $this->conversations = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return $this->getName();
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSystemPrompt(): string
    {
        return $this->systemPrompt;
    }

    public function setSystemPrompt(string $systemPrompt): self
    {
        $this->systemPrompt = $systemPrompt;

        return $this;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function setTemperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getTopP(): float
    {
        return $this->topP;
    }

    public function setTopP(float $topP): self
    {
        $this->topP = $topP;

        return $this;
    }

    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }

    public function setMaxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function getPresencePenalty(): float
    {
        return $this->presencePenalty;
    }

    public function setPresencePenalty(float $presencePenalty): self
    {
        $this->presencePenalty = $presencePenalty;

        return $this;
    }

    public function getFrequencyPenalty(): float
    {
        return $this->frequencyPenalty;
    }

    public function setFrequencyPenalty(float $frequencyPenalty): self
    {
        $this->frequencyPenalty = $frequencyPenalty;

        return $this;
    }

    public function getPreferredApiKey(): ?ApiKey
    {
        return $this->preferredApiKey;
    }

    public function setPreferredApiKey(?ApiKey $preferredApiKey): self
    {
        $this->preferredApiKey = $preferredApiKey;

        return $this;
    }

    public function getSupportFunctions(): ?array
    {
        return $this->supportFunctions;
    }

    public function setSupportFunctions(?array $supportFunctions): void
    {
        $this->supportFunctions = $supportFunctions;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->setActor($this);
        }

        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            // set the owning side to null (unless already changed)
            if ($conversation->getActor() === $this) {
                $conversation->setActor(null);
            }
        }

        return $this;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }}
