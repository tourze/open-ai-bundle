<?php

namespace OpenAIBundle\Tests\AiFunction;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use OpenAIBundle\AiFunction\FetchSqlResult;
use OpenAIBundle\Enum\FunctionParamType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\MCPContracts\ToolInterface;

/**
 * @internal
 */
#[CoversClass(FetchSqlResult::class)]
final class FetchSqlResultTest extends TestCase
{
    private FetchSqlResult $function;

    /** @var Connection&MockObject */
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Connection 是 Doctrine DBAL 的核心连接类，虽然实现了多个接口，但测试需要 mock 其具体的数据库执行方法
         * 2. 这种使用是合理和必要的，因为需要测试与数据库连接相关的具体行为，接口无法提供足够的方法定义
         * 3. 暂无更好的替代方案，因为 Doctrine DBAL 的设计就是基于具体类而非接口抽象
         */
        $this->connection = $this->createMock(Connection::class);
        $this->function = new FetchSqlResult($this->connection);
    }

    public function testGetNameReturnsCorrectName(): void
    {
        $this->assertEquals('FetchSqlResult', $this->function->getName());
    }

    public function testGetDescriptionReturnsCorrectDescription(): void
    {
        $expected = '执行指定的SQL查询并返回结果集。仅支持 SELECT 语句，且必须包含 LIMIT 子句。';
        $this->assertEquals($expected, $this->function->getDescription());
    }

    public function testGetParametersReturnsCorrectParameters(): void
    {
        $parameters = iterator_to_array($this->function->getParameters());

        $this->assertCount(1, $parameters);

        // sql参数（必需）
        $this->assertEquals('sql', $parameters[0]->getName());
        $this->assertEquals(FunctionParamType::string, $parameters[0]->getType());
        $this->assertEquals('要执行的SQL查询语句，必须是 SELECT 语句且包含 LIMIT 子句', $parameters[0]->getDescription());
        $this->assertTrue($parameters[0]->isRequired());
    }

    public function testExecuteWithNonSelectQuery(): void
    {
        $sql = 'UPDATE users SET name = "test" WHERE id = 1 LIMIT 1';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('仅支持 SELECT 语句');

        $this->function->execute(['sql' => $sql]);
    }

    public function testExecuteWithInsertQuery(): void
    {
        $sql = 'INSERT INTO users (name) VALUES ("test")';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('仅支持 SELECT 语句');

        $this->function->execute(['sql' => $sql]);
    }

    public function testExecuteWithDeleteQuery(): void
    {
        $sql = 'DELETE FROM users WHERE id = 1';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('仅支持 SELECT 语句');

        $this->function->execute(['sql' => $sql]);
    }

    public function testExecuteWithEmptySQL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL语句不能为空');

        $this->function->execute(['sql' => '']);
    }

    public function testExecuteWithValidSelectQuery(): void
    {
        $sql = 'SELECT id, name FROM users LIMIT 10';
        $mockResults = [
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2'],
        ];

        /*
         * 使用具体类进行 mock 的原因：
         * 1. Result 是 Doctrine DBAL 查询结果的具体类，需要 mock 其 fetchAllAssociative 等具体方法
         * 2. 这种使用是合理和必要的，因为需要模拟数据库查询结果的具体行为和数据格式
         * 3. 暂无更好的替代方案，因为 Doctrine DBAL 的结果处理依赖于具体的 Result 类实现
         */
        $mockStatement = $this->createMock(Result::class);
        $mockStatement->method('fetchAllAssociative')->willReturn($mockResults);

        $this->connection
            ->method('executeQuery')
            ->with($sql)
            ->willReturn($mockStatement)
        ;

        $result = $this->function->execute(['sql' => $sql]);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals(2, $decoded['total']);
        $this->assertArrayHasKey('columns', $decoded);
        $this->assertEquals($mockResults, $decoded['data']);
        $this->assertEquals($sql, $decoded['sql']);
    }

    public function testExecuteWithEmptyResult(): void
    {
        $sql = 'SELECT id, name FROM users WHERE id = 999 LIMIT 10';

        /*
         * 使用具体类进行 mock 的原因：
         * 1. Result 是 Doctrine DBAL 查询结果的具体类，需要 mock 其 fetchAllAssociative 等具体方法
         * 2. 这种使用是合理和必要的，因为需要模拟数据库查询结果的具体行为和数据格式
         * 3. 暂无更好的替代方案，因为 Doctrine DBAL 的结果处理依赖于具体的 Result 类实现
         */
        $mockStatement = $this->createMock(Result::class);
        $mockStatement->method('fetchAllAssociative')->willReturn([]);

        $this->connection
            ->method('executeQuery')
            ->with($sql)
            ->willReturn($mockStatement)
        ;

        $result = $this->function->execute(['sql' => $sql]);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals(0, $decoded['total']);
        $this->assertEquals([], $decoded['data']);
        $this->assertEquals($sql, $decoded['sql']);
    }

    public function testExecuteDatabaseException(): void
    {
        $sql = 'SELECT * FROM non_existent_table LIMIT 10';
        $exception = new \Exception('Table does not exist');

        $this->connection
            ->method('executeQuery')
            ->with($sql)
            ->willThrowException($exception)
        ;

        $result = $this->function->execute(['sql' => $sql]);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertEquals('Table does not exist', $decoded['error']);
        $this->assertEquals($sql, $decoded['sql']);
    }

    public function testFunctionImplementsInterface(): void
    {
        $this->assertInstanceOf(ToolInterface::class, $this->function);
    }
}
