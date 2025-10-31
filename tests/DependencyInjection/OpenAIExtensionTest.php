<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\DependencyInjection;

use OpenAIBundle\DependencyInjection\OpenAIExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(OpenAIExtension::class)]
final class OpenAIExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
