<?php

namespace OpenAIBundle\Service;

use OpenAIBundle\Controller\Admin\ApiKeyCrudController;
use OpenAIBundle\Controller\Admin\CharacterCrudController;
use OpenAIBundle\Controller\Admin\ConversationCrudController;
use OpenAIBundle\Controller\Admin\MessageCrudController;
use Tourze\EasyAdmin\Event\MenuBuilderEvent;

class AdminMenu
{
    public function buildMenu(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $openAiMenu = $menu->addChild('OpenAI', [
            'label' => 'OpenAI',
            'icon' => 'fas fa-robot',
        ]);

        $openAiMenu->addChild('API密钥', [
            'route' => 'open_ai_api_key',
            'routeParameters' => ['crudAction' => 'index'],
            'icon' => 'fas fa-key',
            'controller' => ApiKeyCrudController::class,
        ]);

        $openAiMenu->addChild('AI角色', [
            'route' => 'open_ai_character',
            'routeParameters' => ['crudAction' => 'index'],
            'icon' => 'fas fa-user-robot',
            'controller' => CharacterCrudController::class,
        ]);

        $openAiMenu->addChild('对话管理', [
            'route' => 'open_ai_conversation',
            'routeParameters' => ['crudAction' => 'index'],
            'icon' => 'fas fa-comments',
            'controller' => ConversationCrudController::class,
        ]);

        $openAiMenu->addChild('消息记录', [
            'route' => 'open_ai_message',
            'routeParameters' => ['crudAction' => 'index'],
            'icon' => 'fas fa-message',
            'controller' => MessageCrudController::class,
        ]);
    }
} 