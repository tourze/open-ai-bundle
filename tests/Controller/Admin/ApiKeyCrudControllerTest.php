<?php

namespace OpenAIBundle\Tests\Controller\Admin;

use OpenAIBundle\Controller\Admin\ApiKeyCrudController;
use PHPUnit\Framework\TestCase;

class ApiKeyCrudControllerTest extends TestCase
{
    public function testControllerExtendsAbstractCrudController(): void
    {
        $controller = new ApiKeyCrudController();
        
        $this->assertInstanceOf(ApiKeyCrudController::class, $controller);
    }

    public function testConfigureEntityFqcn(): void
    {
        $this->assertEquals(\OpenAIBundle\Entity\ApiKey::class, ApiKeyCrudController::getEntityFqcn());
    }
}