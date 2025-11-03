<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\Exception\CodeAnalysisException;
use OpenAIBundle\VO\FunctionParam;
use Tourze\MCPContracts\ToolInterface;

class AnalyzeCodeStructure implements ToolInterface
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

    /**
     * @param array<string, mixed> $parameters
     */
    public function execute(array $parameters = []): string
    {
        $filepath = $this->getFilePath($parameters);
        $detail_level = $this->getDetailLevel($parameters);

        if (!$this->validateFile($filepath)) {
            return $this->encodeError('文件不存在');
        }

        try {
            $analysis = $this->analyzeFile($filepath, $detail_level);
            return $this->encodeResult($analysis);
        } catch (\Throwable $e) {
            return $this->encodeError($e->getMessage());
        }
    }

    /**
     * @param \ReflectionClass<object> $reflection
     * @return array<string, mixed>
     */
    private function buildBasicResult(\ReflectionClass $reflection): array
    {
        return [
            'class_name' => $reflection->getName(),
            'namespace' => $reflection->getNamespaceName(),
            'methods' => [],
            'properties' => [],
        ];
    }

    /**
     * @param \ReflectionClass<object> $reflection
     * @return array<int, array<string, mixed>>
     */
    private function analyzeMethods(\ReflectionClass $reflection): array
    {
        $methods = [];

        foreach ($reflection->getMethods() as $method) {
            $methods[] = $this->analyzeMethod($method);
        }

        return $methods;
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeMethod(\ReflectionMethod $method): array
    {
        return [
            'name' => $method->getName(),
            'visibility' => $this->getVisibility($method),
            'parameters' => $this->analyzeParameters($method),
            'return_type' => $this->getReturnType($method),
            'doc_comment' => $method->getDocComment(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function analyzeParameters(\ReflectionMethod $method): array
    {
        return array_map(function ($param) {
            return $this->analyzeParameter($param);
        }, $method->getParameters());
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeParameter(\ReflectionParameter $param): array
    {
        $type = $param->getType();

        return [
            'name' => $param->getName(),
            'type' => $type instanceof \ReflectionType ? (string) $type : 'mixed',
        ];
    }

    private function getReturnType(\ReflectionMethod $method): string
    {
        $returnType = $method->getReturnType();

        return $returnType instanceof \ReflectionType ? (string) $returnType : 'mixed';
    }

    /**
     * @param \ReflectionClass<object> $reflection
     * @return array<int, array<string, mixed>>
     */
    private function analyzeProperties(\ReflectionClass $reflection): array
    {
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            $properties[] = $this->analyzeProperty($property);
        }

        return $properties;
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeProperty(\ReflectionProperty $property): array
    {
        $type = $property->getType();

        return [
            'name' => $property->getName(),
            'visibility' => $this->getVisibility($property),
            'type' => $type instanceof \ReflectionType ? (string) $type : 'mixed',
            'doc_comment' => $property->getDocComment(),
        ];
    }

    /**
     * @return class-string
     */
    private function getClassNameFromFile(string $filepath): string
    {
        $content = file_get_contents($filepath);
        if (false === $content) {
            throw CodeAnalysisException::classNotFound($filepath);
        }
        if (1 === preg_match('/namespace\s+([^;]+);.*class\s+([^\s{]+)/s', $content, $matches)) {
            /** @var class-string $className */
            $className = $matches[1] . '\\' . $matches[2];
            return $className;
        }
        throw CodeAnalysisException::classNotFound($filepath);
    }

    /**
     * @param \ReflectionMethod|\ReflectionProperty $reflection
     */
    private function getVisibility(\ReflectionMethod|\ReflectionProperty $reflection): string
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

    /**
     * @param array<string, mixed> $parameters
     */
    private function getFilePath(array $parameters): string
    {
        $filepath = $parameters['filepath'] ?? '';
        return is_string($filepath) ? $filepath : '';
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function getDetailLevel(array $parameters): string
    {
        $detail_level = $parameters['detail_level'] ?? 'basic';
        return is_string($detail_level) ? $detail_level : 'basic';
    }

    private function validateFile(string $filepath): bool
    {
        return '' !== $filepath && file_exists($filepath);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeFile(string $filepath, string $detail_level): array
    {
        $className = $this->getClassNameFromFile($filepath);
        /** @var class-string $className */
        $reflection = new \ReflectionClass($className);
        $result = $this->buildBasicResult($reflection);

        if ('full' === $detail_level) {
            $result['methods'] = $this->analyzeMethods($reflection);
            $result['properties'] = $this->analyzeProperties($reflection);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function encodeResult(array $result): string
    {
        $encoded = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return false !== $encoded ? $encoded : '{"error": "JSON encoding failed"}';
    }

    private function encodeError(string $message): string
    {
        $result = json_encode(['error' => $message]);
        return false !== $result ? $result : '{"error": "JSON encoding failed"}';
    }
}
