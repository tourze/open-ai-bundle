<?php

namespace OpenAIBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenAIBundle\Enum\ContextLength;
use OpenAIBundle\Repository\ApiKeyRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;

#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[ORM\Table(name: 'ims_open_ai_api_key', options: ['comment' => 'AI服务-API密钥'])]
class ApiKey implements \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_read', 'restful_write'])]
    #[Assert\NotNull]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, options: ['comment' => '密钥标题'])]
    private ?string $title = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'API密钥'])]
    private string $apiKey;

    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    #[ORM\Column(length: 120, options: ['comment' => '调用模型'])]
    private string $model;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Url]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '聊天补全接口URL'])]
    private string $chatCompletionUrl;

    #[Assert\NotNull]
    #[ORM\Column(nullable: true, options: ['comment' => '支持函数调用'])]
    private ?bool $functionCalling = false;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [ContextLength::class, 'cases'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true, enumType: ContextLength::class, options: ['comment' => '上下文长度'])]
    private ?ContextLength $contextLength = null;

    /**
     * @var Collection<int, Message>
     */
    #[Ignore]
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'apiKey')]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return $this->getTitle() ?? '';
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function getChatCompletionUrl(): string
    {
        return $this->chatCompletionUrl;
    }

    public function setChatCompletionUrl(string $chatCompletionUrl): void
    {
        $this->chatCompletionUrl = $chatCompletionUrl;
    }

    public function isFunctionCalling(): ?bool
    {
        return $this->functionCalling;
    }

    public function setFunctionCalling(?bool $functionCalling): void
    {
        $this->functionCalling = $functionCalling;
    }

    public function getContextLength(): ?ContextLength
    {
        return $this->contextLength;
    }

    public function setContextLength(?ContextLength $contextLength): void
    {
        $this->contextLength = $contextLength;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setApiKey($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getApiKey() === $this) {
                $message->setApiKey(null);
            }
        }

        return $this;
    }
}
