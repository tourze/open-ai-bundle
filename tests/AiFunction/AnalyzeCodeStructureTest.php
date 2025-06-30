<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\AnalyzeCodeStructure;
use OpenAIBundle\Exception\CodeAnalysisException;
use PHPUnit\Framework\TestCase;

class AnalyzeCodeStructureTest extends TestCase
{
    private AnalyzeCodeStructure $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new AnalyzeCodeStructure();
    }

    public function testGetName(): void
    {
        $this->assertEquals('AnalyzeCodeStructure', $this->analyzer->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->analyzer->getDescription();
        $this->assertStringContainsString('分析代码文件的结构', $description);
    }

    public function testGetParameters(): void
    {
        $parameters = iterator_to_array($this->analyzer->getParameters());
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('filepath', $parameters[0]->getName());
        $this->assertEquals('detail_level', $parameters[1]->getName());
    }

    public function testExecuteWithNonExistentFile(): void
    {
        $result = $this->analyzer->execute(['filepath' => '/nonexistent/file.php']);
        $decoded = json_decode($result, true);
        
        $this->assertArrayHasKey('error', $decoded);
        $this->assertEquals('文件不存在', $decoded['error']);
    }

    public function testExecuteWithInvalidPhpFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_invalid_');
        file_put_contents($tempFile, 'invalid php content');
        
        try {
            $result = $this->analyzer->execute(['filepath' => $tempFile]);
            $decoded = json_decode($result, true);
            
            $this->assertArrayHasKey('error', $decoded);
        } finally {
            unlink($tempFile);
        }
    }
}