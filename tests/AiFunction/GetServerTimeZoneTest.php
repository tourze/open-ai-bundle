<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\GetServerTimeZone;
use PHPUnit\Framework\TestCase;

class GetServerTimeZoneTest extends TestCase
{
    private GetServerTimeZone $function;

    protected function setUp(): void
    {
        $this->function = new GetServerTimeZone();
    }

    public function testGetName_returnsCorrectName(): void
    {
        $this->assertEquals('GetServerTimeZone', $this->function->getName());
    }

    public function testGetDescription_returnsCorrectDescription(): void
    {
        $this->assertEquals('获取服务器当前时区信息', $this->function->getDescription());
    }

    public function testGetParameters_returnsEmptyParameters(): void
    {
        $parameters = iterator_to_array($this->function->getParameters());
        
        $this->assertEmpty($parameters);
        $this->assertCount(0, $parameters);
    }

    public function testExecute_returnsCurrentTimeZone(): void
    {
        $result = $this->function->execute();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        
        // 验证返回的是有效的时区标识符
        $timeZones = timezone_identifiers_list();
        $this->assertContains($result, $timeZones, '返回的时区应该是有效的时区标识符');
    }

    public function testExecute_withParameters(): void
    {
        $parameters = ['unused' => 'parameter'];
        $result = $this->function->execute($parameters);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testExecute_returnsConsistentResult(): void
    {
        $result1 = $this->function->execute();
        $result2 = $this->function->execute();

        // 在同一次执行中应该返回相同的时区
        $this->assertEquals($result1, $result2);
    }

    public function testExecute_returnsValidTimeZoneFormat(): void
    {
        $result = $this->function->execute();

        // 验证时区格式，通常是 "Area/Location" 格式
        if (strpos($result, '/') !== false) {
            $parts = explode('/', $result);
            $this->assertGreaterThanOrEqual(2, count($parts), '时区应该包含区域和位置');
            $this->assertNotEmpty($parts[0], '时区区域不能为空');
            $this->assertNotEmpty($parts[1], '时区位置不能为空');
        } else {
            // 某些时区可能是简单格式如 "UTC"
            $this->assertNotEmpty($result);
        }
    }

    public function testExecute_canCreateDateTimeZone(): void
    {
        $result = $this->function->execute();

        // 验证返回的时区能用于创建 DateTimeZone 对象
        $this->expectNotToPerformAssertions();
        new \DateTimeZone($result);
    }

    public function testExecute_matchesPhpDefaultTimeZone(): void
    {
        $result = $this->function->execute();
        $phpTimeZone = date_default_timezone_get();

        $this->assertEquals($phpTimeZone, $result, '应该返回PHP默认时区');
    }

    public function testFunction_implementsInterface(): void
    {
        $this->assertInstanceOf(\OpenAIBundle\AiFunction\AiFunctionInterface::class, $this->function);
    }

    public function testExecute_returnType(): void
    {
        $result = $this->function->execute();

        $this->assertIsString($result);
    }

    public function testExecute_withCommonTimeZones(): void
    {
        $result = $this->function->execute();
        
        $commonTimeZones = [
            'UTC',
            'America/New_York',
            'America/Los_Angeles',
            'Europe/London',
            'Europe/Berlin',
            'Asia/Tokyo',
            'Asia/Shanghai',
            'Australia/Sydney'
        ];

        // 如果不是常见时区之一，至少应该是有效的时区
        if (!in_array($result, $commonTimeZones)) {
            $this->assertTrue(
                in_array($result, timezone_identifiers_list()),
                "时区 '$result' 应该是有效的时区标识符"
            );
        } else {
            $this->assertContains($result, $commonTimeZones);
        }
    }

    public function testExecute_offsetInformation(): void
    {
        $result = $this->function->execute();
        
        $timezone = new \DateTimeZone($result);
        $now = new \DateTime('now', $timezone);
        $offset = $timezone->getOffset($now);

        // 验证时区偏移量在合理范围内 (-12小时到+14小时)
        $this->assertGreaterThanOrEqual(-12 * 3600, $offset, '时区偏移不应该小于-12小时');
        $this->assertLessThanOrEqual(14 * 3600, $offset, '时区偏移不应该大于+14小时');
    }

    public function testExecute_timeZoneAbbreviation(): void
    {
        $result = $this->function->execute();
        
        $timezone = new \DateTimeZone($result);
        $now = new \DateTime('now', $timezone);
        $abbreviation = $timezone->getName();

        $this->assertEquals($result, $abbreviation, '时区名称应该一致');
    }

    public function testConstant_nameValue(): void
    {
        $this->assertEquals('GetServerTimeZone', GetServerTimeZone::NAME);
    }

    public function testExecute_daylightSavingTimeHandling(): void
    {
        $result = $this->function->execute();
        
        $timezone = new \DateTimeZone($result);
        $summer = new \DateTime('2023-07-01', $timezone);
        $winter = new \DateTime('2023-01-01', $timezone);
        
        $summerOffset = $timezone->getOffset($summer);
        $winterOffset = $timezone->getOffset($winter);

        // 验证夏令时处理（如果适用）
        $this->assertIsInt($summerOffset);
        $this->assertIsInt($winterOffset);
        
        // 夏令时偏移差不应该超过2小时
        $difference = abs($summerOffset - $winterOffset);
        $this->assertLessThanOrEqual(2 * 3600, $difference, '夏令时偏移差不应该超过2小时');
    }

    public function testExecute_multipleCallsNoSideEffects(): void
    {
        $originalTimezone = date_default_timezone_get();
        
        // 执行多次函数调用
        for ($i = 0; $i < 5; $i++) {
            $this->function->execute();
        }
        
        // 验证没有副作用
        $this->assertEquals($originalTimezone, date_default_timezone_get(), '函数执行不应该改变系统时区设置');
    }

    public function testExecute_performanceIsAcceptable(): void
    {
        $startTime = microtime(true);
        
        $this->function->execute();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // 函数应该在1秒内完成
        $this->assertLessThan(1.0, $executionTime, '函数执行时间应该少于1秒');
    }
} 