<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\GetCodeContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\MCPContracts\ToolInterface;

/**
 * @internal
 */
#[CoversClass(GetCodeContext::class)]
final class GetCodeContextTest extends TestCase
{
    private GetCodeContext $function;

    protected function setUp(): void
    {
        parent::setUp();
        $this->function = new GetCodeContext();
    }

    public function testGetName(): void
    {
        $this->assertEquals('GetCodeContext', $this->function->getName());
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
        // 创建临时测试文件
        $tempFile = tempnam(sys_get_temp_dir(), 'getcontext_test_');
        file_put_contents($tempFile, '<?php
namespace Test\Namespace;

use Some\TestClass;
use Another\Dependency;

class TestClass
{
    public function testMethod()
    {
        $variable = "test_value";
        return $variable;
    }
}');

        try {
            // 测试获取上下文
            $result = $this->function->execute([
                'filepath' => $tempFile,
                'line_number' => 10,
                'context_lines' => 3,
            ]);

            $this->assertJson($result);
            $data = json_decode($result, true);

            $this->assertArrayHasKey('current_line', $data);
            $this->assertArrayHasKey('context', $data);
            $this->assertArrayHasKey('imports', $data);

            $this->assertEquals(10, $data['current_line']['number']);
            $this->assertNotEmpty($data['context']);
            $this->assertEquals('Test\Namespace', $data['imports']['namespace']);
            $this->assertContains('Some\TestClass', $data['imports']['use_statements']);

            // 测试文件不存在的情况
            $result = $this->function->execute([
                'filepath' => '/invalid/path/file.php',
                'line_number' => 1,
            ]);

            $data = json_decode($result, true);
            $this->assertArrayHasKey('error', $data);
            $this->assertEquals('文件不存在', $data['error']);
        } finally {
            unlink($tempFile);
        }
    }
}
