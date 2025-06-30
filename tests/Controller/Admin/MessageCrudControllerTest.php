<?php

namespace OpenAIBundle\Tests\Controller\Admin;

use OpenAIBundle\Controller\Admin\MessageCrudController;
use PHPUnit\Framework\TestCase;

class MessageCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(\OpenAIBundle\Entity\Message::class, MessageCrudController::getEntityFqcn());
    }
}