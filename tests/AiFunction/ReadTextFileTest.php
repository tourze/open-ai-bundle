<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\ReadTextFile;
use OpenAIBundle\Enum\FunctionParamType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ReadTextFile::class)]
final class ReadTextFileTest extends TestCase
{
    private ReadTextFile $function;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->function = new ReadTextFile();
        $this->tempDir = sys_get_temp_dir() . '/open_ai_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            if (false !== $files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testGetNameReturnsCorrectName(): void
    {
        $this->assertEquals('ReadTextFile', $this->function->getName());
    }

    public function testGetDescriptionReturnsCorrectDescription(): void
    {
        $this->assertEquals('读取文本文件的内容', $this->function->getDescription());
    }

    public function testGetParametersReturnsCorrectParameters(): void
    {
        $parameters = iterator_to_array($this->function->getParameters());

        $this->assertCount(2, $parameters);

        // 第一个参数：filepath（必需）
        $this->assertEquals('filepath', $parameters[0]->getName());
        $this->assertEquals(FunctionParamType::string, $parameters[0]->getType());
        $this->assertEquals('要读取的文件路径', $parameters[0]->getDescription());
        $this->assertTrue($parameters[0]->isRequired());

        // 第二个参数：encoding（可选）
        $this->assertEquals('encoding', $parameters[1]->getName());
        $this->assertEquals(FunctionParamType::string, $parameters[1]->getType());
        $this->assertEquals('文件编码（默认：UTF-8）', $parameters[1]->getDescription());
        $this->assertFalse($parameters[1]->isRequired());
    }

    public function testExecuteWithValidFile(): void
    {
        $content = 'Hello, World!';
        $filename = $this->tempDir . '/test.txt';
        file_put_contents($filename, $content);

        $parameters = ['filepath' => $filename];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals($content, $decoded['content']);
        $this->assertEquals(strlen($content), $decoded['size']);
        $this->assertEquals('UTF-8', $decoded['encoding']);
    }

    public function testExecuteWithNonExistentFile(): void
    {
        $parameters = ['filepath' => '/path/to/nonexistent/file.txt'];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertEquals('文件不存在', $decoded['error']);
    }

    public function testExecuteWithUnreadableFile(): void
    {
        $filename = $this->tempDir . '/unreadable.txt';
        file_put_contents($filename, 'test content');
        chmod($filename, 0o000); // 移除所有权限

        $parameters = ['filepath' => $filename];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertEquals('文件不可读', $decoded['error']);

        // 恢复权限以便清理
        chmod($filename, 0o644);
    }

    public function testExecuteWithEmptyFile(): void
    {
        $filename = $this->tempDir . '/empty.txt';
        file_put_contents($filename, '');

        $parameters = ['filepath' => $filename];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals('', $decoded['content']);
        $this->assertEquals(0, $decoded['size']);
        $this->assertEquals('UTF-8', $decoded['encoding']);
    }

    public function testExecuteWithUnicodeContent(): void
    {
        $content = '你好世界！这是中文测试内容。🚀💻✅';
        $filename = $this->tempDir . '/unicode.txt';
        file_put_contents($filename, $content);

        $parameters = ['filepath' => $filename];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals($content, $decoded['content']);
        $this->assertEquals(strlen($content), $decoded['size']);
        $this->assertEquals('UTF-8', $decoded['encoding']);
    }

    public function testExecuteWithMultilineContent(): void
    {
        $content = "Line 1\nLine 2\nLine 3\n中文行";
        $filename = $this->tempDir . '/multiline.txt';
        file_put_contents($filename, $content);

        $parameters = ['filepath' => $filename];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals($content, $decoded['content']);
        $this->assertEquals(strlen($content), $decoded['size']);
    }

    public function testExecuteWithSpecifiedEncoding(): void
    {
        $content = 'Test content with encoding';
        $filename = $this->tempDir . '/encoded.txt';
        file_put_contents($filename, $content);

        $parameters = ['filepath' => $filename, 'encoding' => 'UTF-8'];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals($content, $decoded['content']);
        $this->assertEquals('UTF-8', $decoded['encoding']);
    }

    public function testExecuteWithLargeFile(): void
    {
        $content = str_repeat('Large file content. ', 1000);
        $filename = $this->tempDir . '/large.txt';
        file_put_contents($filename, $content);

        $parameters = ['filepath' => $filename];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals($content, $decoded['content']);
        $this->assertEquals(strlen($content), $decoded['size']);
    }

    public function testExecuteWithSpecialCharacters(): void
    {
        $content = "Special chars: #@$%^&*()_+{}|:<>?[]\\;',./`~";
        $filename = $this->tempDir . '/special.txt';
        file_put_contents($filename, $content);

        $parameters = ['filepath' => $filename];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals($content, $decoded['content']);
    }

    public function testExecuteWithJsonContent(): void
    {
        $jsonContent = '{"key": "value", "number": 123, "array": [1, 2, 3]}';
        $filename = $this->tempDir . '/test.json';
        file_put_contents($filename, $jsonContent);

        $parameters = ['filepath' => $filename];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals($jsonContent, $decoded['content']);
        $this->assertEquals(strlen($jsonContent), $decoded['size']);
    }

    public function testExecuteReturnsWellFormedJson(): void
    {
        $content = 'Test content';
        $filename = $this->tempDir . '/json_test.txt';
        file_put_contents($filename, $content);

        $parameters = ['filepath' => $filename];
        $result = $this->function->execute($parameters);

        $this->assertJson($result);
        $decoded = json_decode($result, true);

        // 验证JSON结构
        $this->assertArrayHasKey('content', $decoded);
        $this->assertArrayHasKey('size', $decoded);
        $this->assertArrayHasKey('encoding', $decoded);
        $this->assertIsInt($decoded['size']);
    }
}
