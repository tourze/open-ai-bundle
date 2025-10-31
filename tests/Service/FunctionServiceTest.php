<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Service;

use OpenAIBundle\Entity\Character;
use OpenAIBundle\Service\FunctionService;
use OpenAIBundle\VO\ToolCall;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(FunctionService::class)]
#[RunTestsInSeparateProcesses]
final class FunctionServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，不需要特殊设置
    }

    public function testGenerateToolsArrayWithNoFunctions(): void
    {
        $character = new class extends Character {
            /**
             * @return array<string>
             */
            public function getSupportFunctions(): array
            {
                return [];
            }
        };

        $functionService = self::getService(FunctionService::class);
        $result = $functionService->generateToolsArray($character);
        $this->assertEmpty($result);
    }

    public function testGenerateToolsArrayWithCharacterFiltering(): void
    {
        $character = new class extends Character {
            public function getSupportFunctions(): ?array
            {
                return null;
            }
        };

        $functionService = self::getService(FunctionService::class);
        $result = $functionService->generateToolsArray($character);
        // 测试基本功能，不依赖具体的 AI 函数实现
        $this->assertIsArray($result);
    }

    public function testInvokeReturnsEmptyStringWhenNoFunctionFound(): void
    {
        $toolCall = new ToolCall(
            'test-id',
            '0',
            'function',
            'non-existent-function',
            []
        );

        $functionService = self::getService(FunctionService::class);
        $result = $functionService->invoke($toolCall);
        $this->assertSame('', $result);
    }

    public function testServiceCanBeInstantiatedFromContainer(): void
    {
        // 验证服务可以从容器中正确获取
        $functionService = self::getService(FunctionService::class);
        $this->assertInstanceOf(FunctionService::class, $functionService);
    }
}
