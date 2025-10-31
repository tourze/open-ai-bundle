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

        $errorMessage = $this->validateFile($filepath);
        if (null !== $errorMessage) {
            return $this->encodeError($errorMessage);
        }

        $content = $this->readFileContent($filepath);
        if (null === $content) {
            return $this->encodeError('读取文件失败');
        }

        $content = $this->convertEncoding($content, $encoding);

        return $this->encodeResult($content);
    }

    private function validateFile(string $filepath): ?string
    {
        if (!file_exists($filepath)) {
            return '文件不存在';
        }

        if (!is_readable($filepath)) {
            return '文件不可读';
        }

        return null;
    }

    private function readFileContent(string $filepath): ?string
    {
        $content = file_get_contents($filepath);

        return false !== $content ? $content : null;
    }

    private function convertEncoding(string $content, string $encoding): string
    {
        if ('UTF-8' !== strtoupper($encoding)) {
            $converted = mb_convert_encoding($content, 'UTF-8', $encoding);

            return false !== $converted ? $converted : $content;
        }

        return $content;
    }

    private function encodeError(string $message): string
    {
        $result = json_encode(['error' => $message]);

        return false !== $result ? $result : '';
    }

    private function encodeResult(string $content): string
    {
        $result = json_encode([
            'content' => $content,
            'size' => strlen($content),
            'encoding' => 'UTF-8',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return false !== $result ? $result : '';
    }
}
