<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\GetTableFields;
use OpenAIBundle\Enum\FunctionParamType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;
use Tourze\MCPContracts\ToolInterface;

/**
 * @internal
 */
#[CoversClass(GetTableFields::class)]
final class GetTableFieldsTest extends TestCase
{
    private GetTableFields $function;

    /** @var EntityService<object>&MockObject */
    private EntityService $entityService;

    protected function setUp(): void
    {
        parent::setUp();
        /*
         * 使用具体类进行 mock 的原因：
         * 1. EntityService 是一个具体的服务类，提供了数据库实体相关的具体功能，测试需要 mock 其特定的 getTableFields 方法
         * 2. 这种使用是合理和必要的，因为该服务承担了数据库表结构分析的具体职责，接口无法提供足够的方法约束
         * 3. 暂无更好的替代方案，因为 EntityService 是该 Bundle 的核心服务类，其具体实现对于测试来说是必需的
         */
        $this->entityService = $this->createMock(EntityService::class);
        $this->function = new GetTableFields($this->entityService);
    }

    public function testGetNameReturnsCorrectName(): void
    {
        $this->assertEquals('GetTableFields', $this->function->getName());
    }

    public function testGetDescriptionReturnsCorrectDescription(): void
    {
        $expected = '返回指定表的所有字段定义，包含字段名、类型和说明，调用前应使用 GetTableList 获取表名';
        $this->assertEquals($expected, $this->function->getDescription());
    }

    public function testGetParametersReturnsCorrectParameters(): void
    {
        $parameters = iterator_to_array($this->function->getParameters());

        $this->assertCount(1, $parameters);

        // table_name参数（必需）
        $this->assertEquals('table_name', $parameters[0]->getName());
        $this->assertEquals(FunctionParamType::string, $parameters[0]->getType());
        $this->assertEquals('表名', $parameters[0]->getDescription());
        $this->assertTrue($parameters[0]->isRequired());
    }

    public function testExecuteWithValidTableName(): void
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
            ->willReturn($expectedResult)
        ;

        $parameters = ['table_name' => $tableName];
        $result = $this->function->execute($parameters);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecuteCallsEntityServiceWithCorrectParameter(): void
    {
        $tableName = 'products';
        $expectedResult = 'Product table fields';

        $this->entityService
            ->method('getTableFields')
            ->with($this->equalTo($tableName))
            ->willReturn($expectedResult)
        ;

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecuteWithEmptyTableName(): void
    {
        // 空表名时不应该调用 entityService，直接返回错误
        $this->entityService
            ->expects($this->never())
            ->method('getTableFields')
        ;

        $result = $this->function->execute(['table_name' => '']);

        $this->assertEquals('{"error":"\u8868\u540d\u4e0d\u80fd\u4e3a\u7a7a"}', $result);
    }

    public function testExecuteWithNonExistentTable(): void
    {
        $tableName = 'non_existent_table';
        $errorMessage = '表不存在';

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($errorMessage)
        ;

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($errorMessage, $result);
    }

    public function testExecuteWithComplexTableStructure(): void
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
            ->willReturn($complexResult)
        ;

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($complexResult, $result);
        $this->assertStringContainsString('订单主键', $result);
        $this->assertStringContainsString('外键约束', $result);
    }

    public function testExecuteWithUnicodeTableName(): void
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
            ->willReturn($unicodeResult)
        ;

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($unicodeResult, $result);
        $this->assertStringContainsString('用户ID', $result);
        $this->assertStringContainsString('电子邮箱地址', $result);
    }

    public function testExecuteEntityServiceThrowsException(): void
    {
        $tableName = 'error_table';

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willThrowException(new \RuntimeException('Database error'))
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $this->function->execute(['table_name' => $tableName]);
    }

    public function testExecuteWithSpecialCharactersInTableName(): void
    {
        $tableName = 'table_with_special-chars_123';
        $expectedResult = 'Special table fields';

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($expectedResult)
        ;

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecuteMultipleCallsSameTable(): void
    {
        $tableName = 'categories';
        $expectedResult = 'Categories table structure';

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($expectedResult)
        ;

        // 多次调用同一表应该产生相同结果
        $result1 = $this->function->execute(['table_name' => $tableName]);
        $result2 = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($expectedResult, $result1);
        $this->assertEquals($expectedResult, $result2);
        $this->assertEquals($result1, $result2);
    }

    public function testFunctionImplementsInterface(): void
    {
        $this->assertInstanceOf(ToolInterface::class, $this->function);
    }

    public function testExecuteWithEmptyResult(): void
    {
        $tableName = 'empty_table';

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn('')
        ;

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals('', $result);
    }

    public function testExecuteWithLargeTableStructure(): void
    {
        $tableName = 'large_table';

        // 模拟大表结构
        $fields = [];
        for ($i = 1; $i <= 50; ++$i) {
            $fields[] = "| field_{$i} | varchar(255) | 字段{$i}说明 |";
        }
        $largeResult = "# {$tableName} 表字段\n\n" . implode("\n", $fields);

        $this->entityService
            ->method('getTableFields')
            ->with($tableName)
            ->willReturn($largeResult)
        ;

        $result = $this->function->execute(['table_name' => $tableName]);

        $this->assertEquals($largeResult, $result);
        $this->assertStringContainsString('field_1', $result);
        $this->assertStringContainsString('field_50', $result);
    }
}
