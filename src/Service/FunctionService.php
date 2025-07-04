<?php

namespace OpenAIBundle\Service;

use OpenAIBundle\Entity\Character;
use OpenAIBundle\Enum\ToolType;
use OpenAIBundle\VO\FunctionParam;
use OpenAIBundle\VO\ToolCall;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Tourze\MCPContracts\ToolInterface;

class FunctionService
{
    public function __construct(
        #[TaggedIterator(tag: ToolInterface::SERVICE_TAG_NAME)] private readonly iterable $aiFunctions,
        private readonly LoggerInterface                                             $logger,
    ) {
    }

    /**
     * 生成OpenAI需要的tools格式
     */
    public function generateToolsArray(Character $character): array
    {
        $tools = [];

        foreach ($this->aiFunctions as $aiFunction) {
            /** @var ToolInterface $aiFunction */
            $supportFunctions = $character->getSupportFunctions();
            if (null !== $supportFunctions && count($supportFunctions) > 0 && !in_array($aiFunction->getName(), $supportFunctions)) {
                continue;
            }

            $tools[] = [
                'type' => ToolType::function->value, // 目前只有这个类型
                'function' => $this->generateFunctionArray($aiFunction),
            ];
        }

        return $tools;
    }

    /**
     * 参考一些在线文档，这里返回的一定是字符串的
     *
     * @see https://www.volcengine.com/docs/82379/1298454#messageparam
     */
    public function invoke(ToolCall $toolCall): string
    {
        foreach ($this->aiFunctions as $aiFunction) {
            /** @var ToolInterface $aiFunction */
            if ($aiFunction->getName() === $toolCall->getFunctionName()) {
                try {
                    return $aiFunction->execute($toolCall->getFunctionArguments());
                } catch (\Throwable $exception) {
                    $this->logger->error('调用本地函数发生异常', [
                        'function' => $toolCall->getFunctionName(),
                        'arguments' => $toolCall->getFunctionArguments(),
                        'exception' => $exception,
                    ]);

                    return '函数执行发生异常：' . $exception->getMessage();
                }
            }
        }

        return '';
    }

    private function generateFunctionArray(ToolInterface $aiFunction): array
    {
        $parameters = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];
        foreach ($aiFunction->getParameters() as $parameter) {
            /** @var FunctionParam $parameter */
            if ($parameter->isRequired()) {
                $parameters['required'][] = $parameter->getName();
            }
            $parameters['properties'][$parameter->getName()] = [
                'type' => $parameter->getType(),
                'description' => $parameter->getDescription(),
            ];
        }
        if (empty($parameters['properties'])) {
            $parameters['properties'] = (object) [];
        }

        return [
            'name' => $aiFunction->getName(),
            'description' => $aiFunction->getDescription(),
            'parameters' => $parameters,
        ];
    }
}
