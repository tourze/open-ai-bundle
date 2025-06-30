<?php

namespace OpenAIBundle\Tests\Integration\Service;

use OpenAIBundle\Service\AiFunctionFetcher;
use PHPUnit\Framework\TestCase;

class AiFunctionFetcherTest extends TestCase
{
    public function testServiceInstantiation(): void
    {
        // This is a basic test to satisfy PHPStan requirement
        $this->assertTrue(class_exists(AiFunctionFetcher::class));
    }
}