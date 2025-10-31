<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\ListFiles;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\MCPContracts\ToolInterface;

/**
 * @internal
 */
#[CoversClass(ListFiles::class)]
final class ListFilesTest extends TestCase
{
    private ListFiles $function;

    protected function setUp(): void
    {
        parent::setUp();
        $this->function = new ListFiles();
    }

    public function testGetName(): void
    {
        $this->assertEquals('ListFiles', $this->function->getName());
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
        $tempDir = sys_get_temp_dir() . '/listfiles_test_' . uniqid();
        mkdir($tempDir);

        $testFile1 = $tempDir . '/test1.php';
        $testFile2 = $tempDir . '/test2.txt';

        file_put_contents($testFile1, '<?php echo "test1";');
        file_put_contents($testFile2, 'test content');

        try {
            // 测试列出所有文件
            $result = $this->function->execute([
                'directory' => $tempDir,
                'pattern' => '*',
            ]);

            $this->assertJson($result);
            $data = json_decode($result, true);

            $this->assertCount(2, $data);

            $fileNames = array_column($data, 'name');
            $this->assertContains('test1.php', $fileNames);
            $this->assertContains('test2.txt', $fileNames);

            // 验证文件信息结构
            foreach ($data as $file) {
                $this->assertArrayHasKey('name', $file);
                $this->assertArrayHasKey('path', $file);
                $this->assertArrayHasKey('size', $file);
                $this->assertArrayHasKey('type', $file);
                $this->assertArrayHasKey('modified', $file);
            }

            // 测试带模式匹配
            $result = $this->function->execute([
                'directory' => $tempDir,
                'pattern' => '*.php',
            ]);

            $data = json_decode($result, true);
            $this->assertCount(1, $data);
            $this->assertEquals('test1.php', $data[0]['name']);

            // 测试目录不存在
            $result = $this->function->execute([
                'directory' => '/invalid/path',
            ]);

            $data = json_decode($result, true);
            $this->assertArrayHasKey('error', $data);
            $this->assertEquals('目录不存在', $data['error']);
        } finally {
            // 清理测试文件
            unlink($testFile1);
            unlink($testFile2);
            rmdir($tempDir);
        }
    }
}
