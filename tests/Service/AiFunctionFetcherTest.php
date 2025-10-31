<?php

namespace OpenAIBundle\Tests\Service;

use OpenAIBundle\Service\AiFunctionFetcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AiFunctionFetcher::class)]
#[RunTestsInSeparateProcesses]
final class AiFunctionFetcherTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，不需要特殊设置
    }

    public function testGenSelectDataReturnsIterableOfFunctionData(): void
    {
        $service = self::getService(AiFunctionFetcher::class);
        $this->assertInstanceOf(AiFunctionFetcher::class, $service);

        $selectData = iterator_to_array($service->genSelectData());
        $this->assertIsArray($selectData);

        if (count($selectData) > 0) {
            $firstItem = $selectData[0];
            $this->assertArrayHasKey('label', $firstItem);
            $this->assertArrayHasKey('text', $firstItem);
            $this->assertArrayHasKey('value', $firstItem);
            $this->assertArrayHasKey('name', $firstItem);
        }
    }
}
