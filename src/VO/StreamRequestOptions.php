<?php

namespace OpenAIBundle\VO;

/**
 * StreamRequestOptions VO 对象，用于封装流式请求的选项参数
 */
class StreamRequestOptions
{
    private bool $debug = false;

    private ?string $model = null;

    private float $temperature = 0.7;

    private float $topP = 1.0;

    private int $maxTokens = 2000;

    private float $presencePenalty = 0.0;

    private float $frequencyPenalty = 0.0;

    /** @var array<int, array{type: string, function?: array<string, mixed>}>|null */
    private ?array $tools = null;

    private bool $stream = true;

    /** @var array<string, mixed> */
    private array $streamOptions = [
        'include_usage' => true,
    ];

    /**
     * 额外的自定义选项
     *
     * @var array<string, mixed>
     */
    private array $extraOptions = [];

    /**
     * @param array<int, array{type: string, function?: array<string, mixed>}>|null $tools
     * @param array<string, mixed>|null                                             $extraOptions
     */
    public function __construct(
        ?bool $debug = null,
        ?string $model = null,
        ?float $temperature = null,
        ?float $topP = null,
        ?int $maxTokens = null,
        ?float $presencePenalty = null,
        ?float $frequencyPenalty = null,
        ?array $tools = null,
        ?array $extraOptions = null,
    ) {
        $this->initializeProperties([
            'debug' => $debug,
            'model' => $model,
            'temperature' => $temperature,
            'topP' => $topP,
            'maxTokens' => $maxTokens,
            'presencePenalty' => $presencePenalty,
            'frequencyPenalty' => $frequencyPenalty,
            'tools' => $tools,
            'extraOptions' => $extraOptions,
        ]);
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function initializeProperties(array $properties): void
    {
        foreach ($properties as $property => $value) {
            if (null === $value) {
                continue;
            }

            match ($property) {
                'debug' => $this->debug = $value,
                'model' => $this->model = $value,
                'temperature' => $this->temperature = $value,
                'topP' => $this->topP = $value,
                'maxTokens' => $this->maxTokens = $value,
                'presencePenalty' => $this->presencePenalty = $value,
                'frequencyPenalty' => $this->frequencyPenalty = $value,
                'tools' => $this->tools = $value,
                'stream' => $this->stream = $value,
                'streamOptions' => $this->streamOptions = $value,
                'extraOptions' => $this->extraOptions = $value,
                default => null,
            };
        }
    }

    /**
     * 从数组创建实例
     *
     * @param array<string, mixed> $options
     */
    public static function fromArray(array $options): self
    {
        $knownKeys = [
            'debug', 'model', 'temperature', 'top_p', 'max_tokens',
            'presence_penalty', 'frequency_penalty', 'tools',
        ];

        $extraOptions = array_diff_key($options, array_flip($knownKeys));

        return new self(
            debug: $options['debug'] ?? null,
            model: $options['model'] ?? null,
            temperature: $options['temperature'] ?? null,
            topP: $options['top_p'] ?? null,
            maxTokens: $options['max_tokens'] ?? null,
            presencePenalty: $options['presence_penalty'] ?? null,
            frequencyPenalty: $options['frequency_penalty'] ?? null,
            tools: $options['tools'] ?? null,
            extraOptions: $extraOptions,
        );
    }

    /**
     * 转换为 API 请求的数组格式
     *
     * @return array<string, mixed>
     */
    public function toRequestArray(?string $defaultModel = null): array
    {
        $requestArray = [
            'model' => $this->model ?? $defaultModel,
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'max_tokens' => $this->maxTokens,
            'presence_penalty' => $this->presencePenalty,
            'frequency_penalty' => $this->frequencyPenalty,
            'stream' => $this->stream,
            'stream_options' => $this->streamOptions,
        ];

        if (null !== $this->tools) {
            $requestArray['tools'] = $this->tools;
        }

        // 合并额外选项
        return array_merge($requestArray, $this->extraOptions);
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): void
    {
        $this->model = $model;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function setTemperature(float $temperature): void
    {
        $this->temperature = $temperature;
    }

    public function getTopP(): float
    {
        return $this->topP;
    }

    public function setTopP(float $topP): void
    {
        $this->topP = $topP;
    }

    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }

    public function setMaxTokens(int $maxTokens): void
    {
        $this->maxTokens = $maxTokens;
    }

    public function getPresencePenalty(): float
    {
        return $this->presencePenalty;
    }

    public function setPresencePenalty(float $presencePenalty): void
    {
        $this->presencePenalty = $presencePenalty;
    }

    public function getFrequencyPenalty(): float
    {
        return $this->frequencyPenalty;
    }

    public function setFrequencyPenalty(float $frequencyPenalty): void
    {
        $this->frequencyPenalty = $frequencyPenalty;
    }

    /**
     * @return array<int, array{type: string, function?: array<string, mixed>}>|null
     */
    public function getTools(): ?array
    {
        return $this->tools;
    }

    /**
     * @param array<int, array{type: string, function?: array<string, mixed>}>|null $tools
     */
    public function setTools(?array $tools): void
    {
        $this->tools = $tools;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraOptions(): array
    {
        return $this->extraOptions;
    }

    /**
     * @param array<string, mixed> $extraOptions
     */
    public function setExtraOptions(array $extraOptions): void
    {
        $this->extraOptions = $extraOptions;
    }
}
