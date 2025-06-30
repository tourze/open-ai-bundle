<?php

namespace OpenAIBundle\Tests\Controller\Admin;

use OpenAIBundle\Controller\Admin\ConversationCrudController;
use PHPUnit\Framework\TestCase;

class ConversationCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(\OpenAIBundle\Entity\Conversation::class, ConversationCrudController::getEntityFqcn());
    }
}