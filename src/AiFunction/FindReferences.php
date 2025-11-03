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
        [$symbol, $searchPath, $symbolType] = $this->extractParameters($parameters);

        $validationResult = $this->validateParameters($symbol, $searchPath, $symbolType);
        if (null !== $validationResult) {
            return $validationResult;
        }

        $references = $this->searchReferences($searchPath, $symbol, $symbolType);

        return $this->encodeResults($symbol, $symbolType, $references);
    }

    /**
     * @return array<int, string>
     */
    private function findPhpFiles(string $dir): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * @return array<int, array<string, int|string>>
     */
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
                return 1 === preg_match("/\\b(new|extends|implements|use)\\s+.*\\b{$symbol}\\b/", $line);
            case 'function':
                return 1 === preg_match("/\\b{$symbol}\\s*\\(/", $line);
            case 'constant':
                return 1 === preg_match("/\\b(const|define)\\s+{$symbol}\\b/", $line);
            default:
                return true;
        }
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array{string, string, string}
     */
    private function extractParameters(array $parameters): array
    {
        $symbol = $parameters['symbol'] ?? '';
        $searchPath = $parameters['search_path'] ?? '';
        $symbolType = $parameters['symbol_type'] ?? 'all';

        return [
            is_string($symbol) ? $symbol : '',
            is_string($searchPath) ? $searchPath : '',
            is_string($symbolType) ? $symbolType : 'all',
        ];
    }

    private function validateParameters(string $symbol, string $searchPath, string $symbolType): ?string
    {
        if ('' === $symbol || '' === $searchPath) {
            return $this->encodeError('参数类型错误');
        }

        if (!is_dir($searchPath)) {
            return $this->encodeError('搜索路径不存在');
        }

        return null;
    }

    /**
     * @return array<string, array<int, array<string, int|string>>>
     */
    private function searchReferences(string $searchPath, string $symbol, string $symbolType): array
    {
        $references = [];
        $files = $this->findPhpFiles($searchPath);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (false === $content) {
                continue;
            }

            $matches = $this->findSymbolReferences($content, $symbol, $symbolType);
            if ([] !== $matches) {
                $references[$file] = $matches;
            }
        }

        return $references;
    }

    /**
     * @param array<string, array<int, array<string, int|string>>> $references
     */
    private function encodeResults(string $symbol, string $symbolType, array $references): string
    {
        $result = json_encode([
            'symbol' => $symbol,
            'type' => $symbolType,
            'references' => $references,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return false !== $result ? $result : '';
    }

    private function encodeError(string $message): string
    {
        $result = json_encode(['error' => $message]);
        return false !== $result ? $result : '{"error": "Encoding failed"}';
    }
}
