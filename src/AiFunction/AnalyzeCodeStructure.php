<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\VO\FunctionParam;

class AnalyzeCodeStructure implements AiFunctionInterface
{
    public function getName(): string
    {
        return 'AnalyzeCodeStructure';
    }

    public function getDescription(): string
    {
        return '分析代码文件的结构，包括类、方法、属性等';
    }

    public function getParameters(): \Traversable
    {
        yield new FunctionParam('filepath', FunctionParamType::string, '要分析的文件路径', true);
        yield new FunctionParam('detail_level', FunctionParamType::string, '分析详细程度(basic/full)', false);
    }

    public function execute(array $parameters = []): string
    {
        $filepath = $parameters['filepath'] ?? '';
        $detail_level = $parameters['detail_level'] ?? 'basic';

        if (!file_exists($filepath)) {
            return json_encode(['error' => '文件不存在']);
        }

        try {
            $reflection = new \ReflectionClass($this->getClassNameFromFile($filepath));

            $result = [
                'class_name' => $reflection->getName(),
                'namespace' => $reflection->getNamespaceName(),
                'methods' => [],
                'properties' => [],
            ];

            if ('full' === $detail_level) {
                // 分析方法
                foreach ($reflection->getMethods() as $method) {
                    $result['methods'][] = [
                        'name' => $method->getName(),
                        'visibility' => $this->getVisibility($method),
                        'parameters' => array_map(function ($param) {
                            return [
                                'name' => $param->getName(),
                                'type' => $param->getType() ? $param->getType()->getName() : 'mixed',
                            ];
                        }, $method->getParameters()),
                        'return_type' => $method->getReturnType() ? $method->getReturnType()->getName() : 'mixed',
                        'doc_comment' => $method->getDocComment(),
                    ];
                }

                // 分析属性
                foreach ($reflection->getProperties() as $property) {
                    $result['properties'][] = [
                        'name' => $property->getName(),
                        'visibility' => $this->getVisibility($property),
                        'type' => $property->getType() ? $property->getType()->getName() : 'mixed',
                        'doc_comment' => $property->getDocComment(),
                    ];
                }
            }

            return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    private function getClassNameFromFile(string $filepath): string
    {
        $content = file_get_contents($filepath);
        if (preg_match('/namespace\s+([^;]+);.*class\s+([^\s{]+)/s', $content, $matches)) {
            return $matches[1] . '\\' . $matches[2];
        }
        throw new \RuntimeException('无法从文件中提取类名');
    }

    private function getVisibility($reflection): string
    {
        if ($reflection->isPublic()) {
            return 'public';
        }
        if ($reflection->isProtected()) {
            return 'protected';
        }
        if ($reflection->isPrivate()) {
            return 'private';
        }

        return 'unknown';
    }
}
