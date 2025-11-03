<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\VO\FunctionParam;
use Tourze\MCPContracts\ToolInterface;

class ListFiles implements ToolInterface
{
    public function getName(): string
    {
        return 'ListFiles';
    }

    public function getDescription(): string
    {
        return '列出指定目录下的所有文件';
    }

    public function getParameters(): \Traversable
    {
        yield new FunctionParam('directory', FunctionParamType::string, '要列出文件的目录路径', true);
        yield new FunctionParam('pattern', FunctionParamType::string, '文件匹配模式（例如：*.php）', false);
    }

    public function execute(array $parameters = []): string
    {
        $directory = $parameters['directory'] ?? '.';
        $pattern = $parameters['pattern'] ?? '*';

        if (!is_string($directory) || !is_string($pattern)) {
            return $this->encodeError('参数类型错误');
        }

        if (!is_dir($directory)) {
            return $this->encodeError('目录不存在');
        }

        $files = $this->getFiles($directory, $pattern);
        if (null === $files) {
            return $this->encodeError('获取文件列表失败');
        }

        $result = $this->formatFileList($files);

        return $this->encodeResult($result);
    }

    /**
     * @return array<int, string>|null
     */
    private function getFiles(string $directory, string $pattern): ?array
    {
        $files = glob($directory . '/' . $pattern);

        return false !== $files ? $files : null;
    }

    /**
     * @param array<int, string> $files
     * @return array<int, array<string, mixed>>
     */
    private function formatFileList(array $files): array
    {
        return array_map(function (string $file): array {
            return $this->formatFileInfo($file);
        }, $files);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFileInfo(string $file): array
    {
        $filesize = filesize($file);
        $filetype = filetype($file);
        $filemtime = filemtime($file);

        return [
            'name' => basename($file),
            'path' => $file,
            'size' => false !== $filesize ? $filesize : 0,
            'type' => false !== $filetype ? $filetype : 'unknown',
            'modified' => false !== $filemtime ? date('Y-m-d H:i:s', $filemtime) : '',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $result
     */
    private function encodeResult(array $result): string
    {
        $jsonResult = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return false !== $jsonResult ? $jsonResult : '';
    }

    private function encodeError(string $message): string
    {
        $result = json_encode(['error' => $message]);

        return false !== $result ? $result : '';
    }
}
