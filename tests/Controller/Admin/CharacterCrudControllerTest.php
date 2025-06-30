<?php

namespace OpenAIBundle\Tests\Controller\Admin;

use OpenAIBundle\Controller\Admin\CharacterCrudController;
use PHPUnit\Framework\TestCase;

class CharacterCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(\OpenAIBundle\Entity\Character::class, CharacterCrudController::getEntityFqcn());
    }
}