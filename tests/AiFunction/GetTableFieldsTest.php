<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\GetTableFields;
use OpenAIBundle\Enum\FunctionParamType;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;

class GetTableFieldsTest extends TestCase
{
    private GetTableFields $function;
    private EntityService $entityService;

    protected function setUp(): void
    {
        $this->entityService = $this->createMock(EntityService::class);
        $this->function = new GetTableFields($this->entityService);
    }

    public function testGetName_returnsCorrectName(): void
    {
        $this->assertEquals('GetTableFields', $this->function->getName());
    }

    public function testGetDescription_returnsCorrectDescription(): void
    {
        $expected = '返回指定表的所有字段定义，包含字段名、类型和说明，调用前应使用 GetTableList 获取表名';
        $this->assertEquals($expected, $this->function->getDescription());
    }

    public function testGetParameters_returnsCorrectParameters(): void
    {
        $parameters = iterator_to_array($this->function->getParameters());
        
        $this->assertCount(1, $parameters);
        
        // table_name参数（必需）
        $this->assertEquals('table_name', $parameters[0]->getName());
        $this->assertEquals(FunctionParamType::string, $parameters[0]->getType());
        $this->assertEquals('表名', $parameters[0]->getDescription());
        $this->assertTrue($parameters[0]->isRequired());
    }

    public function testExecute_withValidTableName(): void
    {
        $tableName = 'users';
        $expectedResult = <<<'FIELDS'
# users 表字段

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 主键ID |
| username | varchar(50) | 用户名 |
| email | varchar(100) | 邮箱地址 |
| created_at | datetime | 创建时间 |
FIELDS;

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($expectedResult);

        $parameters = ['table_name' => $tableName];
        $result = $this->function->execute($parameters);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecute_callsEntityServiceWithCorrectParameter(): void
    {
        $tableName = 'products';
        $expectedResult = 'Product table fields';

        $this->entityService
            ->method('getTableFields')
            ->with($this->equalTo($tableName))
            ->willReturn($expectedResult);

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecute_withEmptyTableName(): void
    {
        $this->entityService
            ->method('getTableFields')
            ->with('')
            ->willReturn('空表名错误');

        $result = $this->function->execute(['table_name' => '']);

        $this->assertEquals('空表名错误', $result);
    }

    public function testExecute_withNonExistentTable(): void
    {
        $tableName = 'non_existent_table';
        $errorMessage = '表不存在';

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($errorMessage);

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($errorMessage, $result);
    }

    public function testExecute_withComplexTableStructure(): void
    {
        $tableName = 'orders';
        $complexResult = <<<'COMPLEX'
# orders 表字段详细信息

## 基本字段
| 字段名 | 类型 | 是否必需 | 默认值 | 说明 |
|--------|------|----------|--------|------|
| id | bigint | 是 | AUTO_INCREMENT | 订单主键 |
| order_number | varchar(32) | 是 | - | 订单编号 |
| user_id | bigint | 是 | - | 用户ID (外键) |
| total_amount | decimal(10,2) | 是 | 0.00 | 订单总金额 |
| status | enum | 是 | 'pending' | 订单状态 |
| created_at | timestamp | 是 | CURRENT_TIMESTAMP | 创建时间 |
| updated_at | timestamp | 是 | CURRENT_TIMESTAMP | 更新时间 |

## 索引信息
- PRIMARY KEY: id
- UNIQUE KEY: order_number  
- INDEX: user_id
- INDEX: status, created_at

## 外键约束
- user_id REFERENCES users(id) ON DELETE CASCADE
COMPLEX;

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($complexResult);

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($complexResult, $result);
        $this->assertStringContainsString('订单主键', $result);
        $this->assertStringContainsString('外键约束', $result);
    }

    public function testExecute_withUnicodeTableName(): void
    {
        $tableName = '用户表';
        $unicodeResult = <<<'UNICODE'
# 用户表 字段说明

| 字段名 | 类型 | 说明 |
|--------|------|------|
| 用户ID | int | 主键标识符 |
| 用户名 | varchar(50) | 登录用户名 |
| 姓名 | varchar(100) | 真实姓名 |
| 邮箱 | varchar(255) | 电子邮箱地址 |
UNICODE;

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($unicodeResult);

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($unicodeResult, $result);
        $this->assertStringContainsString('用户ID', $result);
        $this->assertStringContainsString('电子邮箱地址', $result);
    }

    public function testExecute_entityServiceThrowsException(): void
    {
        $tableName = 'error_table';

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willThrowException(new \RuntimeException('Database error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $this->function->execute(['table_name' => $tableName]);
    }

    public function testExecute_withSpecialCharactersInTableName(): void
    {
        $tableName = 'table_with_special-chars_123';
        $expectedResult = 'Special table fields';

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($expectedResult);

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecute_multipleCallsSameTable(): void
    {
        $tableName = 'categories';
        $expectedResult = 'Categories table structure';

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($expectedResult);

        // 多次调用同一表应该产生相同结果
        $result1 = $this->function->execute(['table_name' => $tableName]);
        $result2 = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($expectedResult, $result1);
        $this->assertEquals($expectedResult, $result2);
        $this->assertEquals($result1, $result2);
    }

    public function testFunction_implementsInterface(): void
    {
        $this->assertInstanceOf(\OpenAIBundle\AiFunction\AiFunctionInterface::class, $this->function);
    }


    public function testExecute_withEmptyResult(): void
    {
        $tableName = 'empty_table';

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn('');

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals('', $result);
    }

    public function testExecute_withLargeTableStructure(): void
    {
        $tableName = 'large_table';
        
        // 模拟大表结构
        $fields = [];
        for ($i = 1; $i <= 50; $i++) {
            $fields[] = "| field_{$i} | varchar(255) | 字段{$i}说明 |";
        }
        $largeResult = "# {$tableName} 表字段\n\n" . implode("\n", $fields);

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($largeResult);

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($largeResult, $result);
        $this->assertStringContainsString('field_1', $result);
        $this->assertStringContainsString('field_50', $result);
    }
} 