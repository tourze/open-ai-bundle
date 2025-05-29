<?php

namespace OpenAIBundle\VO;

use Yiisoft\Json\Json;

class ChoiceVO
{
    public function __construct(
        private readonly array $delta,
        private readonly ?string $finishReason = null,
        private readonly ?int $index = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['delta'],
            $data['finish_reason'] ?? null,
            $data['index'] ?? null
        );
    }

    public function getContent(): ?string
    {
        return $this->delta['content'] ?? null;
    }

    public function getReasoningContent(): ?string
    {
        return $this->delta['reasoning_content'] ?? null;
    }

    public function getRole(): ?string
    {
        return $this->delta['role'] ?? null;
    }

    public function getFinishReason(): ?string
    {
        return $this->finishReason;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function getToolCalls(): ?array
    {
        return $this->delta['tool_calls'] ?? null;
    }

    /**
     * @return ToolCall[]
     *
     * @throws \JsonException
     */
    public function getDecodeToolCalls(): array
    {
        dump($this->delta);
        $result = [];
        foreach ($this->delta['tool_calls'] ?? [] as $item) {
            // ID是必须要有的
            if (empty($item['id'])) {
                continue;
            }
            $result[] = new ToolCall(
                $item['id'],
                $item['index'],
                $item['type'],
                $item['function']['name'],
                Json::decode($item['function']['arguments']) ?: [],
            );
        }

        return $result;
    }
}
