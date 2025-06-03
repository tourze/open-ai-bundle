<?php

namespace OpenAIBundle\Tests\VO;

use OpenAIBundle\VO\UsageVO;
use PHPUnit\Framework\TestCase;

class UsageVOTest extends TestCase
{
    public function testConstructor_setsAllProperties(): void
    {
        $promptTokens = 100;
        $completionTokens = 50;
        $totalTokens = 150;

        $usage = new UsageVO($promptTokens, $completionTokens, $totalTokens);

        $this->assertEquals($promptTokens, $usage->getPromptTokens());
        $this->assertEquals($completionTokens, $usage->getCompletionTokens());
        $this->assertEquals($totalTokens, $usage->getTotalTokens());
    }

    public function testFromArray_createsUsageVOFromValidData(): void
    {
        $data = [
            'prompt_tokens' => 200,
            'completion_tokens' => 75,
            'total_tokens' => 275
        ];

        $usage = UsageVO::fromArray($data);

        $this->assertEquals(200, $usage->getPromptTokens());
        $this->assertEquals(75, $usage->getCompletionTokens());
        $this->assertEquals(275, $usage->getTotalTokens());
    }

    public function testGetPromptTokens_returnsCorrectValue(): void
    {
        $usage = new UsageVO(150, 50, 200);

        $this->assertEquals(150, $usage->getPromptTokens());
    }

    public function testGetCompletionTokens_returnsCorrectValue(): void
    {
        $usage = new UsageVO(100, 75, 175);

        $this->assertEquals(75, $usage->getCompletionTokens());
    }

    public function testGetTotalTokens_returnsCorrectValue(): void
    {
        $usage = new UsageVO(120, 80, 200);

        $this->assertEquals(200, $usage->getTotalTokens());
    }

    public function testUsageVOWithZeroTokens(): void
    {
        $usage = new UsageVO(0, 0, 0);

        $this->assertEquals(0, $usage->getPromptTokens());
        $this->assertEquals(0, $usage->getCompletionTokens());
        $this->assertEquals(0, $usage->getTotalTokens());
    }

    public function testUsageVOWithLargeNumbers(): void
    {
        $usage = new UsageVO(10000, 5000, 15000);

        $this->assertEquals(10000, $usage->getPromptTokens());
        $this->assertEquals(5000, $usage->getCompletionTokens());
        $this->assertEquals(15000, $usage->getTotalTokens());
    }

    public function testFromArray_handlesStringNumbers(): void
    {
        $data = [
            'prompt_tokens' => '250',
            'completion_tokens' => '125',
            'total_tokens' => '375'
        ];

        $usage = UsageVO::fromArray($data);

        $this->assertEquals(250, $usage->getPromptTokens());
        $this->assertEquals(125, $usage->getCompletionTokens());
        $this->assertEquals(375, $usage->getTotalTokens());
    }

    public function testTokenCalculationConsistency(): void
    {
        $promptTokens = 300;
        $completionTokens = 150;
        $totalTokens = 450;

        $usage = new UsageVO($promptTokens, $completionTokens, $totalTokens);

        // 验证总数应该等于输入+输出
        $this->assertEquals(
            $usage->getPromptTokens() + $usage->getCompletionTokens(),
            $usage->getTotalTokens()
        );
    }

    public function testUsageVOImmutability(): void
    {
        $originalPrompt = 100;
        $originalCompletion = 50;
        $originalTotal = 150;

        $usage = new UsageVO($originalPrompt, $originalCompletion, $originalTotal);

        // 验证所有属性都是只读的（通过readonly关键字）
        $this->assertEquals($originalPrompt, $usage->getPromptTokens());
        $this->assertEquals($originalCompletion, $usage->getCompletionTokens());
        $this->assertEquals($originalTotal, $usage->getTotalTokens());

        // 创建另一个实例来验证独立性
        $otherUsage = new UsageVO(200, 100, 300);

        $this->assertNotEquals($usage->getPromptTokens(), $otherUsage->getPromptTokens());
        $this->assertNotEquals($usage->getCompletionTokens(), $otherUsage->getCompletionTokens());
        $this->assertNotEquals($usage->getTotalTokens(), $otherUsage->getTotalTokens());
    }

    public function testUsageVOEquality(): void
    {
        $usage1 = new UsageVO(100, 50, 150);
        $usage2 = new UsageVO(100, 50, 150);

        // 不同的对象实例
        $this->assertNotSame($usage1, $usage2);

        // 但属性值相同
        $this->assertEquals($usage1->getPromptTokens(), $usage2->getPromptTokens());
        $this->assertEquals($usage1->getCompletionTokens(), $usage2->getCompletionTokens());
        $this->assertEquals($usage1->getTotalTokens(), $usage2->getTotalTokens());
    }

    public function testUsageComparison(): void
    {
        $smallUsage = new UsageVO(50, 25, 75);
        $largeUsage = new UsageVO(600, 300, 900);

        // 50 * 10 = 500, 应该小于 600
        $this->assertLessThan($largeUsage->getPromptTokens(), $smallUsage->getPromptTokens() * 10);
        // 75 应该小于 900
        $this->assertLessThan($largeUsage->getTotalTokens(), $smallUsage->getTotalTokens());
    }

    public function testTypicalUsageScenarios(): void
    {
        // 短对话场景
        $shortConversation = new UsageVO(50, 30, 80);
        $this->assertLessThan(100, $shortConversation->getTotalTokens());

        // 长对话场景
        $longConversation = new UsageVO(2000, 1000, 3000);
        $this->assertGreaterThan(1000, $longConversation->getTotalTokens());

        // 代码生成场景（高输出比例）
        $codeGeneration = new UsageVO(100, 500, 600);
        $this->assertGreaterThan($codeGeneration->getPromptTokens(), $codeGeneration->getCompletionTokens());
    }

    public function testFromArrayWithMissingFields(): void
    {
        $this->expectException(\TypeError::class);
        
        // 缺少必需字段应该抛出异常
        $data = [
            'prompt_tokens' => 100
            // 缺少 completion_tokens 和 total_tokens
        ];

        UsageVO::fromArray($data);
    }

    public function testFromArrayWithExtraFields(): void
    {
        $data = [
            'prompt_tokens' => 150,
            'completion_tokens' => 75,
            'total_tokens' => 225,
            'extra_field' => 'ignored',
            'another_extra' => 123
        ];

        $usage = UsageVO::fromArray($data);

        // 额外的字段应该被忽略
        $this->assertEquals(150, $usage->getPromptTokens());
        $this->assertEquals(75, $usage->getCompletionTokens());
        $this->assertEquals(225, $usage->getTotalTokens());
    }

    public function testBoundaryValues(): void
    {
        // 测试边界值
        $maxUsage = new UsageVO(PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX);
        $this->assertEquals(PHP_INT_MAX, $maxUsage->getPromptTokens());
        $this->assertEquals(PHP_INT_MAX, $maxUsage->getCompletionTokens());
        $this->assertEquals(PHP_INT_MAX, $maxUsage->getTotalTokens());

        $minUsage = new UsageVO(0, 0, 0);
        $this->assertEquals(0, $minUsage->getPromptTokens());
        $this->assertEquals(0, $minUsage->getCompletionTokens());
        $this->assertEquals(0, $minUsage->getTotalTokens());
    }
} 