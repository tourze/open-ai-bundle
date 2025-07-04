<?php

namespace OpenAIBundle\VO;

class StreamChunkVO extends StreamResponseVO
{
    private array $rawData;

    public function __construct(
        string $id,
        int $created,
        string $model,
        string $systemFingerprint,
        string $object,
        array $choices,
        ?UsageVO $usage = null,
    ) {
        parent::__construct($id, $created, $model, $systemFingerprint, $object, $choices, $usage);
    }

    public static function fromArray(array $data): self
    {
        $result = new self(
            $data['id'],
            $data['created'],
            $data['model'],
            $data['system_fingerprint'] ?? '',
            $data['object'],
            array_map(
                fn (array $choice) => ChoiceVO::fromArray($choice),
                $data['choices']
            ),
            isset($data['usage']) ? UsageVO::fromArray($data['usage']) : null
        );
        $result->setRawData($data);

        return $result;
    }

    /**
     * @return ChoiceVO[]
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    public function getUsage(): ?UsageVO
    {
        return $this->usage;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function setRawData(array $rawData): void
    {
        $this->rawData = $rawData;
    }
}
