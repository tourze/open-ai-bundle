<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\VO\FunctionParam;
use Tourze\MCPContracts\ToolInterface;

class ReadTextFile implements ToolInterface
{
    public function getName(): string
    {
        return 'ReadTextFile';
    }

    public function getDescription(): string
    {
        return '读取文本文件的内容';
    }

    public function getParameters(): \Traversable
    {
        yield new FunctionParam('filepath', FunctionParamType::string, '要读取的文件路径', true);
        yield new FunctionParam('encoding', FunctionParamType::string, '文件编码（默认：UTF-8）', false);
    }

    public function execute(array $parameters = []): string
    {
        $filepath = $parameters['filepath'] ?? '';
        $encoding = $parameters['encoding'] ?? 'UTF-8';

        if (!file_exists($filepath)) {
            return json_encode(['error' => '文件不存在']);
        }

        if (!is_readable($filepath)) {
            return json_encode(['error' => '文件不可读']);
        }

        $content = file_get_contents($filepath);

        // 如果指定的编码不是 UTF-8，进行转换
        if ('UTF-8' !== strtoupper($encoding)) {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return json_encode([
            'content' => $content,
            'size' => strlen($content),
            'encoding' => 'UTF-8',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
