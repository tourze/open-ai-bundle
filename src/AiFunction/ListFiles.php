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

        if (!is_dir($directory)) {
            return json_encode(['error' => '目录不存在']);
        }

        $files = glob($directory . '/' . $pattern);
        $result = array_map(function ($file) {
            return [
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'type' => filetype($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }, $files);

        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
