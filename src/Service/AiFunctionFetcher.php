<?php

namespace OpenAIBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Tourze\EnumExtra\SelectDataFetcher;
use Tourze\MCPContracts\ToolInterface;

#[Autoconfigure(public: true)]
class AiFunctionFetcher implements SelectDataFetcher
{
    /**
     * @param iterable<ToolInterface> $functions
     */
    public function __construct(
        #[AutowireIterator(tag: ToolInterface::SERVICE_TAG_NAME)] private readonly iterable $functions,
    ) {
    }

    /**
     * @return iterable<array{label: string, text: string, value: string, name: string}>
     */
    public function genSelectData(): iterable
    {
        foreach ($this->functions as $function) {
            /** @var ToolInterface $function */
            $desc = "{$function->getName()} - {$function->getDescription()}";

            yield [
                'label' => $desc,
                'text' => $desc,
                'value' => $function->getName(),
                'name' => $desc,
            ];
        }
    }
}
