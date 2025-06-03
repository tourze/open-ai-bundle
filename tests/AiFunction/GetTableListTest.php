<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\GetTableList;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;

class GetTableListTest extends TestCase
{
    private GetTableList $function;
    private EntityService $entityService;

    protected function setUp(): void
    {
        $this->entityService = $this->createMock(EntityService::class);
        $this->function = new GetTableList($this->entityService);
    }

    public function testGetName_returnsCorrectName(): void
    {
        $this->assertEquals('GetTableList', $this->function->getName());
    }

    public function testGetDescription_returnsCorrectDescription(): void
    {
        $this->assertEquals('返回数据库中所有表名和说明', $this->function->getDescription());
    }

    public function testGetParameters_returnsEmptyParameters(): void
    {
        $parameters = iterator_to_array($this->function->getParameters());
        
        $this->assertEmpty($parameters);
        $this->assertCount(0, $parameters);
    }

    public function testExecute_callsEntityService(): void
    {
        $expectedResult = "Table list:\n- users\n- posts\n- comments";
        
        $this->entityService
            ->expects($this->once())
            ->method('getAllTableNames')
            ->willReturn($expectedResult);

        $result = $this->function->execute();

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecute_withParameters(): void
    {
        $expectedResult = "Table names and descriptions";
        
        $this->entityService
            ->expects($this->once())
            ->method('getAllTableNames')
            ->willReturn($expectedResult);

        $parameters = ['unused' => 'parameter'];
        $result = $this->function->execute($parameters);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecute_withEmptyResult(): void
    {
        $this->entityService
            ->expects($this->once())
            ->method('getAllTableNames')
            ->willReturn('');

        $result = $this->function->execute();

        $this->assertEquals('', $result);
    }

    public function testExecute_withComplexTableList(): void
    {
        $complexResult = <<<'MARKDOWN'
# 数据库表列表

## 用户相关表
- `users` - 用户基本信息表
- `user_profiles` - 用户详细资料表

## 内容相关表  
- `posts` - 文章表
- `comments` - 评论表
- `categories` - 分类表

## 系统表
- `migrations` - 数据库迁移记录
- `logs` - 系统日志表
MARKDOWN;

        $this->entityService
            ->expects($this->once())
            ->method('getAllTableNames')
            ->willReturn($complexResult);

        $result = $this->function->execute();

        $this->assertEquals($complexResult, $result);
    }

    public function testConstant_nameValue(): void
    {
        $this->assertEquals('GetTableList', GetTableList::NAME);
    }

    public function testExecute_entityServiceThrowsException(): void
    {
        $this->entityService
            ->expects($this->once())
            ->method('getAllTableNames')
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->function->execute();
    }

    public function testExecute_multipleCallsConsistentBehavior(): void
    {
        $expectedResult = "Consistent table list";
        
        $this->entityService
            ->expects($this->exactly(3))
            ->method('getAllTableNames')
            ->willReturn($expectedResult);

        // 多次调用应该产生一致的结果
        $result1 = $this->function->execute();
        $result2 = $this->function->execute();
        $result3 = $this->function->execute();

        $this->assertEquals($expectedResult, $result1);
        $this->assertEquals($expectedResult, $result2);
        $this->assertEquals($expectedResult, $result3);
        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }

    public function testExecute_withLargeTableList(): void
    {
        // 模拟大量表的情况
        $tableNames = [];
        for ($i = 1; $i <= 100; $i++) {
            $tableNames[] = "- table_{$i} - Description for table {$i}";
        }
        $largeResult = implode("\n", $tableNames);

        $this->entityService
            ->expects($this->once())
            ->method('getAllTableNames')
            ->willReturn($largeResult);

        $result = $this->function->execute();

        $this->assertEquals($largeResult, $result);
        $this->assertStringContainsString('table_1', $result);
        $this->assertStringContainsString('table_100', $result);
    }

    public function testExecute_withUnicodeTableNames(): void
    {
        $unicodeResult = <<<'UNICODE'
# 数据库表列表

- `用户表` - 存储用户基本信息
- `产品表` - 产品目录表  
- `订单表` - 订单记录表
- `日志表` - 系统日志记录
UNICODE;

        $this->entityService
            ->expects($this->once())
            ->method('getAllTableNames')
            ->willReturn($unicodeResult);

        $result = $this->function->execute();

        $this->assertEquals($unicodeResult, $result);
        $this->assertStringContainsString('用户表', $result);
        $this->assertStringContainsString('产品表', $result);
    }

    public function testFunction_implementsInterface(): void
    {
        $this->assertInstanceOf(\OpenAIBundle\AiFunction\AiFunctionInterface::class, $this->function);
    }

    public function testExecute_returnType(): void
    {
        $this->entityService
            ->expects($this->once())
            ->method('getAllTableNames')
            ->willReturn('test result');

        $result = $this->function->execute();

        $this->assertIsString($result);
    }
} 