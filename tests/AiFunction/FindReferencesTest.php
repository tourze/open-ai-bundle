<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\FindReferences;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\MCPContracts\ToolInterface;

/**
 * @internal
 */
#[CoversClass(FindReferences::class)]
final class FindReferencesTest extends TestCase
{
    private FindReferences $function;

    protected function setUp(): void
    {
        parent::setUp();
        $this->function = new FindReferences();
    }

    public function testGetName(): void
    {
        $this->assertEquals('FindReferences', $this->function->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->function->getDescription();
        $this->assertNotEmpty($description);
    }

    public function testGetParameters(): void
    {
        $parameters = iterator_to_array($this->function->getParameters());
        $this->assertNotNull($parameters);
    }

    public function testImplementsToolInterface(): void
    {
        $this->assertInstanceOf(ToolInterface::class, $this->function);
    }

    public function testExecute(): void
    {
        // 创建临时测试目录和文件
        $tempDir = sys_get_temp_dir() . '/findref_test_' . uniqid();
        mkdir($tempDir);

        $testFile = $tempDir . '/test.php';
        file_put_contents($testFile, '<?php
class TestClass {
    public function testMethod() {
        return "test";
    }
}
$obj = new TestClass();');

        try {
            // 测试查找类引用
            $result = $this->function->execute([
                'symbol' => 'TestClass',
                'search_path' => $tempDir,
                'symbol_type' => 'class',
            ]);

            $this->assertJson($result);
            $data = json_decode($result, true);

            $this->assertEquals('TestClass', $data['symbol']);
            $this->assertEquals('class', $data['type']);
            $this->assertArrayHasKey('references', $data);
            $this->assertArrayHasKey($testFile, $data['references']);

            // 测试无效路径
            $result = $this->function->execute([
                'symbol' => 'TestClass',
                'search_path' => '/invalid/path',
                'symbol_type' => 'class',
            ]);

            $data = json_decode($result, true);
            $this->assertArrayHasKey('error', $data);
            $this->assertEquals('搜索路径不存在', $data['error']);
        } finally {
            // 清理测试文件
            unlink($testFile);
            rmdir($tempDir);
        }
    }
}
