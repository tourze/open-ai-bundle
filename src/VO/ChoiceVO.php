<?php

namespace OpenAIBundle\VO;

use Yiisoft\Json\Json;

class ChoiceVO
{
    public function __construct(
        /** @var array<string, mixed> $delta */
        private readonly array $delta,
        private readonly ?string $finishReason = null,
        private readonly ?int $index = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // 处理非流式响应（使用 message 字段）和流式响应（使用 delta 字段）
        $delta = $data['delta'] ?? $data['message'] ?? [];

        return new self(
            $delta,
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

    /**
     * @return array<mixed>|null
     */
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
        //        dump($this->delta);
        $result = [];
        foreach ($this->delta['tool_calls'] ?? [] as $item) {
            // ID是必须要有的
            if (!isset($item['id']) || '' === $item['id']) {
                continue;
            }
            $result[] = new ToolCall(
                $item['id'],
                $item['index'],
                $item['type'],
                $item['function']['name'],
                Json::decode($item['function']['arguments']) ?? [],
            );
        }

        return $result;
    }
}
