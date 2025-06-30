<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\VO\FunctionParam;
use Tourze\MCPContracts\ToolInterface;

class FindReferences implements ToolInterface
{
    public function getName(): string
    {
        return 'FindReferences';
    }

    public function getDescription(): string
    {
        return '查找代码中的符号引用（变量、函数、类等）';
    }

    public function getParameters(): \Traversable
    {
        yield new FunctionParam('symbol', FunctionParamType::string, '要查找的符号名称', true);
        yield new FunctionParam('search_path', FunctionParamType::string, '搜索路径', true);
        yield new FunctionParam('symbol_type', FunctionParamType::string, '符号类型(class/function/constant)', false);
    }

    public function execute(array $parameters = []): string
    {
        $symbol = $parameters['symbol'] ?? '';
        $search_path = $parameters['search_path'] ?? '';
        $symbol_type = $parameters['symbol_type'] ?? 'all';

        if (!is_dir($search_path)) {
            return json_encode(['error' => '搜索路径不存在']);
        }

        $references = [];
        $files = $this->findPhpFiles($search_path);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $matches = $this->findSymbolReferences($content, $symbol, $symbol_type);

            if (!empty($matches)) {
                $references[$file] = $matches;
            }
        }

        return json_encode([
            'symbol' => $symbol,
            'type' => $symbol_type,
            'references' => $references,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function findPhpFiles(string $dir): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function findSymbolReferences(string $content, string $symbol, string $type): array
    {
        $references = [];
        $lines = explode("\n", $content);

        foreach ($lines as $number => $line) {
            if ('all' === $type || $this->matchesSymbolType($line, $symbol, $type)) {
                if (false !== strpos($line, $symbol)) {
                    $references[] = [
                        'line' => $number + 1,
                        'content' => trim($line),
                    ];
                }
            }
        }

        return $references;
    }

    private function matchesSymbolType(string $line, string $symbol, string $type): bool
    {
        switch ($type) {
            case 'class':
                return preg_match("/\\b(new|extends|implements|use)\\s+.*\\b$symbol\\b/", $line);
            case 'function':
                return preg_match("/\\b$symbol\\s*\\(/", $line);
            case 'constant':
                return preg_match("/\\b(const|define)\\s+$symbol\\b/", $line);
            default:
                return true;
        }
    }
}
