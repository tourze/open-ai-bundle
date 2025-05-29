<?php

namespace OpenAIBundle\Service;

use OpenAIBundle\AiFunction\AiFunctionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Tourze\EnumExtra\SelectDataFetcher;

#[Autoconfigure(public: true)]
class AiFunctionFetcher implements SelectDataFetcher
{
    public function __construct(
        #[TaggedIterator(AiFunctionInterface::SERVICE_TAG_NAME)] private readonly iterable $functions,
    ) {
    }

    public function genSelectData(): iterable
    {
        foreach ($this->functions as $function) {
            /** @var AiFunctionInterface $function */
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
