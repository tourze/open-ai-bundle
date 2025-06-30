<?php

namespace OpenAIBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Tourze\EnumExtra\SelectDataFetcher;
use Tourze\MCPContracts\ToolInterface;

#[Autoconfigure(public: true)]
class AiFunctionFetcher implements SelectDataFetcher
{
    public function __construct(
        #[TaggedIterator(tag: ToolInterface::SERVICE_TAG_NAME)] private readonly iterable $functions,
    ) {
    }

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
