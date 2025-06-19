<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\GetServerRandomNumber;
use OpenAIBundle\Enum\FunctionParamType;
use PHPUnit\Framework\TestCase;

class GetServerRandomNumberTest extends TestCase
{
    private GetServerRandomNumber $function;

    protected function setUp(): void
    {
        $this->function = new GetServerRandomNumber();
    }

    public function testGetName_returnsCorrectName(): void
    {
        $this->assertEquals('GetServerRandomNumber', $this->function->getName());
    }

    public function testGetDescription_returnsCorrectDescription(): void
    {
        $this->assertEquals('生成一个服务端随机数', $this->function->getDescription());
    }

    public function testGetParameters_returnsCorrectParameters(): void
    {
        $parameters = iterator_to_array($this->function->getParameters());
        
        $this->assertCount(2, $parameters);
        
        // 第一个参数：min（可选）
        $this->assertEquals('min', $parameters[0]->getName());
        $this->assertEquals(FunctionParamType::integer, $parameters[0]->getType());
        $this->assertEquals('最小值', $parameters[0]->getDescription());
        $this->assertFalse($parameters[0]->isRequired());
        
        // 第二个参数：max（必需）
        $this->assertEquals('max', $parameters[1]->getName());
        $this->assertEquals(FunctionParamType::integer, $parameters[1]->getType());
        $this->assertEquals('最大值', $parameters[1]->getDescription());
        $this->assertTrue($parameters[1]->isRequired());
    }

    public function testExecute_withMinAndMax(): void
    {
        $parameters = ['min' => 10, 'max' => 20];
        
        $result = $this->function->execute($parameters);
        $resultInt = (int) $result;
        $this->assertGreaterThanOrEqual(10, $resultInt);
        $this->assertLessThanOrEqual(20, $resultInt);
    }

    public function testExecute_withOnlyMax(): void
    {
        $parameters = ['max' => 100];
        
        $result = $this->function->execute($parameters);
        $resultInt = (int) $result;
        $this->assertGreaterThanOrEqual(0, $resultInt);
        $this->assertLessThanOrEqual(100, $resultInt);
    }

    public function testExecute_withNoParameters(): void
    {
        $parameters = [];
        
        $result = $this->function->execute($parameters);
        $resultInt = (int) $result;
        $this->assertGreaterThanOrEqual(0, $resultInt);
        $this->assertLessThanOrEqual(PHP_INT_MAX, $resultInt);
    }

    public function testExecute_withNegativeRange(): void
    {
        $parameters = ['min' => -50, 'max' => -10];
        
        $result = $this->function->execute($parameters);
        $resultInt = (int) $result;
        $this->assertGreaterThanOrEqual(-50, $resultInt);
        $this->assertLessThanOrEqual(-10, $resultInt);
    }

    public function testExecute_withSameMinMax(): void
    {
        $parameters = ['min' => 42, 'max' => 42];
        
        $result = $this->function->execute($parameters);
        
        $this->assertEquals('42', $result);
    }

    public function testExecute_withLargeRange(): void
    {
        $parameters = ['min' => 1, 'max' => 1000000];
        
        $result = $this->function->execute($parameters);
        $resultInt = (int) $result;
        $this->assertGreaterThanOrEqual(1, $resultInt);
        $this->assertLessThanOrEqual(1000000, $resultInt);
    }

    public function testExecute_returnsString(): void
    {
        $parameters = ['min' => 1, 'max' => 10];
        
        $result = $this->function->execute($parameters);
        $this->assertMatchesRegularExpression('/^\d+$/', $result);
    }

    public function testMultipleExecutions_produceDifferentResults(): void
    {
        $parameters = ['min' => 1, 'max' => 1000];
        $results = [];
        
        // 执行多次生成随机数
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->function->execute($parameters);
        }
        
        // 检查是否产生了不同的结果（虽然理论上可能全部相同，但概率极小）
        $uniqueResults = array_unique($results);
        $this->assertGreaterThan(1, count($uniqueResults), '随机数生成器应该产生不同的结果');
    }

    public function testExecute_withZeroMax(): void
    {
        $parameters = ['min' => 0, 'max' => 0];
        
        $result = $this->function->execute($parameters);
        
        $this->assertEquals('0', $result);
    }
} 