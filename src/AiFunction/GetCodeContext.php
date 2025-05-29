<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\VO\FunctionParam;

class GetCodeContext implements AiFunctionInterface
{
    public function getName(): string
    {
        return 'GetCodeContext';
    }

    public function getDescription(): string
    {
        return '获取代码上下文信息，包括当前光标位置的相关代码片段和依赖关系';
    }

    public function getParameters(): \Traversable
    {
        yield new FunctionParam('filepath', FunctionParamType::string, '文件路径', true);
        yield new FunctionParam('line_number', FunctionParamType::integer, '当前行号', true);
        yield new FunctionParam('context_lines', FunctionParamType::integer, '上下文行数', false);
    }

    public function execute(array $parameters = []): string
    {
        $filepath = $parameters['filepath'] ?? '';
        $line_number = $parameters['line_number'] ?? 1;
        $context_lines = $parameters['context_lines'] ?? 5;

        if (!file_exists($filepath)) {
            return json_encode(['error' => '文件不存在']);
        }

        try {
            $lines = file($filepath);
            $total_lines = count($lines);

            // 计算上下文范围
            $start = max(0, $line_number - $context_lines - 1);
            $end = min($total_lines, $line_number + $context_lines);

            // 提取上下文代码
            $context = array_slice($lines, $start, $end - $start);

            // 分析当前行的符号
            $current_line = $lines[$line_number - 1] ?? '';
            $symbols = $this->analyzeLineSymbols($current_line);

            // 分析导入的依赖
            $imports = $this->analyzeImports($lines);

            $result = [
                'current_line' => [
                    'number' => $line_number,
                    'content' => trim($current_line),
                    'symbols' => $symbols,
                ],
                'context' => array_map(function ($line, $idx) use ($start) {
                    return [
                        'line_number' => $start + $idx + 1,
                        'content' => trim($line),
                    ];
                }, $context, array_keys($context)),
                'imports' => $imports,
            ];

            return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    private function analyzeLineSymbols(string $line): array
    {
        $symbols = [];

        // 提取变量
        preg_match_all('/\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $line, $matches);
        $symbols['variables'] = $matches[0];

        // 提取函数调用
        preg_match_all('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s*\(/', $line, $matches);
        $symbols['functions'] = array_map(function ($match) {
            return rtrim($match, '(');
        }, $matches[0]);

        // 提取类名
        preg_match_all('/\b[A-Z][a-zA-Z0-9_]*/', $line, $matches);
        $symbols['classes'] = $matches[0];

        return $symbols;
    }

    private function analyzeImports(array $lines): array
    {
        $imports = [];
        $namespace = '';

        foreach ($lines as $line) {
            // 获取命名空间
            if (preg_match('/namespace\s+([^;]+);/', $line, $matches)) {
                $namespace = $matches[1];
            }

            // 获取use语句
            if (preg_match('/use\s+([^;]+);/', $line, $matches)) {
                $imports[] = $matches[1];
            }
        }

        return [
            'namespace' => $namespace,
            'use_statements' => $imports,
        ];
    }
}
