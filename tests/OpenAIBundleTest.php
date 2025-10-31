<?php

declare(strict_types=1);

namespace OpenAiBundle\Tests;

use OpenAIBundle\OpenAIBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(OpenAIBundle::class)]
#[RunTestsInSeparateProcesses]
final class OpenAIBundleTest extends AbstractBundleTestCase
{
}
