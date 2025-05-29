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
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\EasyAdmin\Attribute\Action\Copyable;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Column\BoolColumn;
use Tourze\EasyAdmin\Attribute\Column\CopyColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: 'API密钥')]
#[Creatable]
#[Editable]
#[Deletable]
#[Copyable]
#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[ORM\Table(name: 'ims_open_ai_api_key', options: ['comment' => 'AI服务-API密钥'])]
class ApiKey implements \Stringable
{
    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[BoolColumn]
    #[IndexColumn]
    #[TrackColumn]
    #[Groups(['admin_curd', 'restful_read', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    #[ListColumn(order: 97)]
    #[FormField(order: 97)]
    private ?bool $valid = false;

    #[CopyColumn(suffix: '-复制')]
    #[FormField]
    #[ListColumn]
    #[ORM\Column(length: 100, options: ['comment' => '密钥标题'])]
    private ?string $title = null;

    #[CopyColumn]
    #[FormField]
    #[ListColumn]
    #[ORM\Column(type: 'string', length: 255, options: ['comment' => 'API密钥'])]
    private string $apiKey;

    #[CopyColumn]
    #[ListColumn]
    #[FormField]
    #[ORM\Column(length: 120, options: ['comment' => '调用模型'])]
    private string $model;

    #[CopyColumn]
    #[FormField]
    #[ListColumn]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '聊天补全接口URL'])]
    private string $chatCompletionUrl;

    #[CopyColumn]
    #[BoolColumn]
    #[ListColumn]
    #[FormField]
    #[ORM\Column(nullable: true, options: ['comment' => '支持函数调用'])]
    private ?bool $functionCalling = false;

    #[CopyColumn]
    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: Types::INTEGER, nullable: true, enumType: ContextLength::class, options: ['comment' => '上下文长度'])]
    private ?ContextLength $contextLength = null;

    /**
     * @var Collection<int, Message>
     */
    #[Ignore]
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'apiKey')]
    private Collection $messages;

    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[Groups(['restful_read', 'admin_curd', 'restful_read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'restful_read'])]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getTitle();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getChatCompletionUrl(): string
    {
        return $this->chatCompletionUrl;
    }

    public function setChatCompletionUrl(string $chatCompletionUrl): self
    {
        $this->chatCompletionUrl = $chatCompletionUrl;

        return $this;
    }

    public function isFunctionCalling(): ?bool
    {
        return $this->functionCalling;
    }

    public function setFunctionCalling(?bool $functionCalling): static
    {
        $this->functionCalling = $functionCalling;

        return $this;
    }

    public function getContextLength(): ?ContextLength
    {
        return $this->contextLength;
    }

    public function setContextLength(?ContextLength $contextLength): self
    {
        $this->contextLength = $contextLength;

        return $this;
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

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }
}
