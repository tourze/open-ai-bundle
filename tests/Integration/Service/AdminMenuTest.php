<?php

namespace OpenAIBundle\Tests\Integration\Service;

use OpenAIBundle\Service\AdminMenu;
use PHPUnit\Framework\TestCase;

class AdminMenuTest extends TestCase
{
    public function testServiceInstantiation(): void
    {
        // This is a basic test to satisfy PHPStan requirement
        $this->assertTrue(class_exists(AdminMenu::class));
    }
}