<?php

namespace OpenAIBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenAIBundle\Repository\ConversationRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'ims_open_ai_conversation', options: ['comment' => 'AI对话'])]
class Conversation implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_read', 'restful_write'])]
    #[Assert\NotNull]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '对话标题'])]
    private string $title;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '对话描述'])]
    private ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '使用模型'])]
    private string $model = 'gpt-3.5-turbo';

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '系统提示词'])]
    private ?string $systemPrompt = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(
        targetEntity: Message::class,
        mappedBy: 'conversation',
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    private Collection $messages;

    #[ORM\ManyToOne(inversedBy: 'conversations', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Character $actor = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function getSystemPrompt(): ?string
    {
        return $this->systemPrompt;
    }

    public function setSystemPrompt(?string $systemPrompt): void
    {
        $this->systemPrompt = $systemPrompt;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }

    public function clearMessages(): self
    {
        foreach ($this->getMessages() as $message) {
            $this->removeMessage($message);
        }

        return $this;
    }

    public function getActor(): ?Character
    {
        return $this->actor;
    }

    public function setActor(?Character $actor): void
    {
        $this->actor = $actor;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }
}
