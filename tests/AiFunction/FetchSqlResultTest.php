<?php

namespace OpenAIBundle\Tests\AiFunction;

use Doctrine\DBAL\Connection;
use OpenAIBundle\AiFunction\FetchSqlResult;
use OpenAIBundle\Enum\FunctionParamType;
use PHPUnit\Framework\TestCase;

class FetchSqlResultTest extends TestCase
{
    private FetchSqlResult $function;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->function = new FetchSqlResult($this->connection);
    }

    public function testGetName_returnsCorrectName(): void
    {
        $this->assertEquals('FetchSqlResult', $this->function->getName());
    }

    public function testGetDescription_returnsCorrectDescription(): void
    {
        $expected = '执行指定的SQL查询并返回结果集。仅支持 SELECT 语句，且必须包含 LIMIT 子句。';
        $this->assertEquals($expected, $this->function->getDescription());
    }

    public function testGetParameters_returnsCorrectParameters(): void
    {
        $parameters = iterator_to_array($this->function->getParameters());
        
        $this->assertCount(1, $parameters);
        
        // sql参数（必需）
        $this->assertEquals('sql', $parameters[0]->getName());
        $this->assertEquals(FunctionParamType::string, $parameters[0]->getType());
        $this->assertEquals('要执行的SQL查询语句，必须是 SELECT 语句且包含 LIMIT 子句', $parameters[0]->getDescription());
        $this->assertTrue($parameters[0]->isRequired());
    }

    public function testExecute_withNonSelectQuery(): void
    {
        $sql = 'UPDATE users SET name = "test" WHERE id = 1 LIMIT 1';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('仅支持 SELECT 语句');

        $this->function->execute(['sql' => $sql]);
    }

    public function testExecute_withInsertQuery(): void
    {
        $sql = 'INSERT INTO users (name) VALUES ("test")';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('仅支持 SELECT 语句');

        $this->function->execute(['sql' => $sql]);
    }

    public function testExecute_withDeleteQuery(): void
    {
        $sql = 'DELETE FROM users WHERE id = 1';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('仅支持 SELECT 语句');

        $this->function->execute(['sql' => $sql]);
    }

    public function testExecute_withEmptySQL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL语句不能为空');

        $this->function->execute(['sql' => '']);
    }

    public function testExecute_withValidSelectQuery(): void
    {
        $sql = 'SELECT id, name FROM users LIMIT 10';
        $mockResults = [
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2']
        ];

        $mockStatement = $this->createMock(\Doctrine\DBAL\Result::class);
        $mockStatement->method('fetchAllAssociative')->willReturn($mockResults);

        $this->connection
            ->method('executeQuery')
            ->with($sql)
            ->willReturn($mockStatement);

        $result = $this->function->execute(['sql' => $sql]);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals(2, $decoded['total']);
        $this->assertArrayHasKey('columns', $decoded);
        $this->assertEquals($mockResults, $decoded['data']);
        $this->assertEquals($sql, $decoded['sql']);
    }

    public function testExecute_withEmptyResult(): void
    {
        $sql = 'SELECT id, name FROM users WHERE id = 999 LIMIT 10';

        $mockStatement = $this->createMock(\Doctrine\DBAL\Result::class);
        $mockStatement->method('fetchAllAssociative')->willReturn([]);

        $this->connection
            ->method('executeQuery')
            ->with($sql)
            ->willReturn($mockStatement);

        $result = $this->function->execute(['sql' => $sql]);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertEquals(0, $decoded['total']);
        $this->assertEquals([], $decoded['data']);
        $this->assertEquals($sql, $decoded['sql']);
    }

    public function testExecute_databaseException(): void
    {
        $sql = 'SELECT * FROM non_existent_table LIMIT 10';
        $exception = new \Exception('Table does not exist');

        $this->connection
            ->method('executeQuery')
            ->with($sql)
            ->willThrowException($exception);

        $result = $this->function->execute(['sql' => $sql]);

        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertEquals('Table does not exist', $decoded['error']);
        $this->assertEquals($sql, $decoded['sql']);
    }

    public function testFunction_implementsInterface(): void
    {
        $this->assertInstanceOf(\OpenAIBundle\AiFunction\AiFunctionInterface::class, $this->function);
    }

    public function testExecute_returnType(): void
    {
        $sql = 'SELECT 1 as test LIMIT 1';
        
        $mockStatement = $this->createMock(\Doctrine\DBAL\Result::class);
        $mockStatement->method('fetchAllAssociative')->willReturn([['test' => 1]]);

        $this->connection
            ->method('executeQuery')
            ->willReturn($mockStatement);

        $result = $this->function->execute(['sql' => $sql]);
        $this->assertIsString($result);
    }
} 