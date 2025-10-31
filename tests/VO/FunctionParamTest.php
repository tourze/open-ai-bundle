<?php

namespace OpenAIBundle\Tests\VO;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\VO\FunctionParam;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FunctionParam::class)]
final class FunctionParamTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $name = 'testParam';
        $type = FunctionParamType::string;
        $description = 'Test parameter description';
        $required = true;

        $param = new FunctionParam($name, $type, $description, $required);

        $this->assertEquals($name, $param->getName());
        $this->assertEquals($type, $param->getType());
        $this->assertEquals($description, $param->getDescription());
        $this->assertTrue($param->isRequired());
    }

    public function testGetNameReturnsCorrectName(): void
    {
        $param = new FunctionParam(
            'locationParam',
            FunctionParamType::string,
            'Location parameter',
            true
        );

        $this->assertEquals('locationParam', $param->getName());
    }

    public function testGetTypeReturnsCorrectType(): void
    {
        $param = new FunctionParam(
            'count',
            FunctionParamType::integer,
            'Count parameter',
            false
        );

        $this->assertEquals(FunctionParamType::integer, $param->getType());
    }

    public function testGetDescriptionReturnsCorrectDescription(): void
    {
        $description = 'This is a detailed parameter description';
        $param = new FunctionParam(
            'param1',
            FunctionParamType::boolean,
            $description,
            true
        );

        $this->assertEquals($description, $param->getDescription());
    }

    public function testIsRequiredReturnsTrueForRequiredParam(): void
    {
        $param = new FunctionParam(
            'requiredParam',
            FunctionParamType::string,
            'Required parameter',
            true
        );

        $this->assertTrue($param->isRequired());
    }

    public function testIsRequiredReturnsFalseForOptionalParam(): void
    {
        $param = new FunctionParam(
            'optionalParam',
            FunctionParamType::string,
            'Optional parameter',
            false
        );

        $this->assertFalse($param->isRequired());
    }

    public function testStringTypeFunctionParam(): void
    {
        $param = new FunctionParam(
            'textInput',
            FunctionParamType::string,
            'Text input parameter',
            true
        );

        $this->assertEquals('textInput', $param->getName());
        $this->assertEquals(FunctionParamType::string, $param->getType());
        $this->assertEquals('string', $param->getType()->value);
        $this->assertTrue($param->isRequired());
    }

    public function testIntegerTypeFunctionParam(): void
    {
        $param = new FunctionParam(
            'numericValue',
            FunctionParamType::integer,
            'Numeric value parameter',
            false
        );

        $this->assertEquals('numericValue', $param->getName());
        $this->assertEquals(FunctionParamType::integer, $param->getType());
        $this->assertEquals('integer', $param->getType()->value);
        $this->assertFalse($param->isRequired());
    }

    public function testBooleanTypeFunctionParam(): void
    {
        $param = new FunctionParam(
            'enableFlag',
            FunctionParamType::boolean,
            'Enable flag parameter',
            true
        );

        $this->assertEquals('enableFlag', $param->getName());
        $this->assertEquals(FunctionParamType::boolean, $param->getType());
        $this->assertEquals('boolean', $param->getType()->value);
        $this->assertTrue($param->isRequired());
    }

    public function testParameterWithEmptyDescription(): void
    {
        $param = new FunctionParam(
            'param',
            FunctionParamType::string,
            '',
            false
        );

        $this->assertEquals('', $param->getDescription());
    }

    public function testParameterWithLongDescription(): void
    {
        $longDescription = str_repeat('This is a very long description. ', 100);
        $param = new FunctionParam(
            'param',
            FunctionParamType::string,
            $longDescription,
            true
        );

        $this->assertEquals($longDescription, $param->getDescription());
    }

    public function testParameterWithSpecialCharactersInName(): void
    {
        $param = new FunctionParam(
            'param_with_underscore',
            FunctionParamType::string,
            'Parameter with underscore',
            true
        );

        $this->assertEquals('param_with_underscore', $param->getName());
    }

    public function testParameterWithUnicodeDescription(): void
    {
        $unicodeDescription = '参数描述包含中文字符 🚀 💻';
        $param = new FunctionParam(
            'unicodeParam',
            FunctionParamType::string,
            $unicodeDescription,
            false
        );

        $this->assertEquals($unicodeDescription, $param->getDescription());
    }

    public function testParameterImmutability(): void
    {
        $originalName = 'originalParam';
        $originalType = FunctionParamType::string;
        $originalDescription = 'Original description';
        $originalRequired = true;

        $param = new FunctionParam(
            $originalName,
            $originalType,
            $originalDescription,
            $originalRequired
        );

        // 验证所有属性都是只读的（通过readonly关键字）
        $this->assertEquals($originalName, $param->getName());
        $this->assertEquals($originalType, $param->getType());
        $this->assertEquals($originalDescription, $param->getDescription());
        $this->assertEquals($originalRequired, $param->isRequired());

        // 创建另一个实例来验证独立性
        $otherParam = new FunctionParam(
            'otherParam',
            FunctionParamType::integer,
            'Other description',
            false
        );

        $this->assertNotEquals($param->getName(), $otherParam->getName());
        $this->assertNotEquals($param->getType(), $otherParam->getType());
        $this->assertNotEquals($param->getDescription(), $otherParam->getDescription());
        $this->assertNotEquals($param->isRequired(), $otherParam->isRequired());
    }

    public function testParameterEquality(): void
    {
        $param1 = new FunctionParam(
            'testParam',
            FunctionParamType::string,
            'Test description',
            true
        );

        $param2 = new FunctionParam(
            'testParam',
            FunctionParamType::string,
            'Test description',
            true
        );

        // 由于是不同的对象实例，它们不相等
        $this->assertNotSame($param1, $param2);

        // 但属性值相同
        $this->assertEquals($param1->getName(), $param2->getName());
        $this->assertEquals($param1->getType(), $param2->getType());
        $this->assertEquals($param1->getDescription(), $param2->getDescription());
        $this->assertEquals($param1->isRequired(), $param2->isRequired());
    }

    public function testComplexFunctionSignature(): void
    {
        // 模拟复杂函数的参数定义
        $params = [
            new FunctionParam(
                'query',
                FunctionParamType::string,
                'SQL查询语句',
                true
            ),
            new FunctionParam(
                'limit',
                FunctionParamType::integer,
                '结果数量限制',
                false
            ),
            new FunctionParam(
                'strict_mode',
                FunctionParamType::boolean,
                '是否启用严格模式',
                false
            ),
        ];

        $this->assertCount(3, $params);

        // 验证第一个参数（必需的字符串）
        $this->assertEquals('query', $params[0]->getName());
        $this->assertEquals(FunctionParamType::string, $params[0]->getType());
        $this->assertTrue($params[0]->isRequired());

        // 验证第二个参数（可选的整数）
        $this->assertEquals('limit', $params[1]->getName());
        $this->assertEquals(FunctionParamType::integer, $params[1]->getType());
        $this->assertFalse($params[1]->isRequired());

        // 验证第三个参数（可选的布尔值）
        $this->assertEquals('strict_mode', $params[2]->getName());
        $this->assertEquals(FunctionParamType::boolean, $params[2]->getType());
        $this->assertFalse($params[2]->isRequired());
    }
}
