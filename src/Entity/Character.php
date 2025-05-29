<?php

namespace OpenAIBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenAIBundle\Repository\CharacterRepository;
use OpenAIBundle\Service\AiFunctionFetcher;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Column\BoolColumn;
use Tourze\EasyAdmin\Attribute\Column\CopyColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Column\PictureColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Field\ImagePickerField;
use Tourze\EasyAdmin\Attribute\Field\SelectField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: 'AI角色设定')]
#[Creatable]
#[Editable]
#[Deletable]
#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: 'ims_open_ai_character', options: ['comment' => 'AI角色设定'])]
class Character implements \Stringable
{
    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '角色名称'])]
    private string $name;

    #[ImagePickerField]
    #[PictureColumn]
    #[FormField]
    #[Groups(['restful_read'])]
    #[ListColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '头像'])]
    private ?string $avatar = null;

    #[FormField]
    #[Groups(['restful_read'])]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述'])]
    private ?string $description = null;

    #[FormField]
    #[ORM\Column(type: Types::TEXT, options: ['comment' => '系统提示词'])]
    private string $systemPrompt;

    /**
     * @var float 控制输出的随机性
     *            采样温度。控制了生成文本时对每个候选词的概率分布进行平滑的程度。取值范围为 [0, 1]。
     *            当取值为 0 时模型仅考虑对数概率最大的一个 token。
     *            较高的值（如 0.8）会使输出更加随机，而较低的值（如 0.2）会使输出更加集中确定。通常建议仅调整 temperature 或 top_p 其中之一，不建议两者都修改。
     */
    #[ListColumn(sorter: true)]
    #[FormField]
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '温度参数'])]
    private float $temperature = 1;

    /**
     * @var float 控制输出的多样性
     *            核采样概率阈值。模型会考虑概率质量在 top_p 内的 token 结果。取值范围为 [0, 1]。当取值为 0 时模型仅考虑对数概率最大的一个 token。
     *            如 0.1 意味着只考虑概率质量最高的前 10% 的 token，取值越大生成的随机性越高，取值越低生成的确定性越高。通常建议仅调整 temperature 或 top_p 其中之一，不建议两者都修改。
     */
    #[ListColumn(sorter: true)]
    #[FormField]
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '采样概率阈值'])]
    private float $topP = 0.7;

    #[ListColumn(sorter: true)]
    #[FormField]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '最大生成令牌数'])]
    private int $maxTokens = 2000;

    /**
     * @var float 避免重复主题。果值为正，会根据新 token 到目前为止是否出现在文本中对其进行惩罚，从而增加模型谈论新主题的可能性。取值范围为 [-2.0, 2.0]。
     */
    #[ListColumn(sorter: true)]
    #[FormField]
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '存在惩罚'])]
    private float $presencePenalty = 0.0;

    /**
     * @var float 避免重复用词。如果值为正，会根据新 token 在文本中的出现频率对其进行惩罚，从而降低模型逐字重复的可能性。取值范围为 [-2.0, 2.0]。
     */
    #[ListColumn(sorter: true)]
    #[FormField]
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '频率惩罚'])]
    private float $frequencyPenalty = 0.0;

    #[ListColumn(title: '偏好APIKey')]
    #[FormField(title: '偏好APIKey')]
    #[ORM\ManyToOne(targetEntity: ApiKey::class)]
    #[ORM\JoinColumn(name: 'preferred_api_key_id', nullable: true, onDelete: 'SET NULL')]
    private ?ApiKey $preferredApiKey = null;

    #[CopyColumn]
    #[SelectField(targetEntity: AiFunctionFetcher::class, mode: 'multiple')]
    #[FormField]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '支持函数'])]
    private ?array $supportFunctions = [];

    /**
     * @var Collection<int, Conversation>
     */
    #[Ignore]
    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'actor')]
    private Collection $conversations;

    #[BoolColumn]
    #[IndexColumn]
    #[TrackColumn]
    #[Groups(['admin_curd', 'restful_read', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    #[ListColumn(order: 97)]
    #[FormField(order: 97)]
    private ?bool $valid = false;

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
        $this->conversations = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getName();
    }

    public function getId(): ?string
    {
        return $this->id;
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
