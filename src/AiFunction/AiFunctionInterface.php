<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\VO\FunctionParam;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * 在这里定义一个系统函数
 *
 * @see https://www.volcengine.com/docs/82379/1298454#functiondefinition
 */
#[AutoconfigureTag(self::SERVICE_TAG_NAME)]
interface AiFunctionInterface
{
    final public const SERVICE_TAG_NAME = 'open_ai.function.service';

    /**
     * 返回函数的名称
     */
    public function getName(): string;

    /**
     * 对函数用途的描述，供模型判断何时以及如何调用该工具函数
     */
    public function getDescription(): string;

    /**
     * 函数请求参数，以 JSON Schema 格式描述。
     *
     * @return \Traversable<FunctionParam>
     */
    public function getParameters(): \Traversable;

    /**
     * 执行并返回结果
     */
    public function execute(array $parameters = []): string;
}
