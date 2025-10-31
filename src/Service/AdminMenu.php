<?php

namespace OpenAIBundle\Service;

use Knp\Menu\ItemInterface;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        $openAiMenu = $item->addChild('OpenAI', [
            'label' => 'OpenAI',
            'icon' => 'fas fa-robot',
        ]);

        $openAiMenu->addChild('API密钥')
            ->setUri($this->linkGenerator->getCurdListPage(ApiKey::class))
            ->setAttribute('icon', 'fas fa-key')
        ;

        $openAiMenu->addChild('AI角色')
            ->setUri($this->linkGenerator->getCurdListPage(Character::class))
            ->setAttribute('icon', 'fas fa-user')
        ;

        $openAiMenu->addChild('对话管理')
            ->setUri($this->linkGenerator->getCurdListPage(Conversation::class))
            ->setAttribute('icon', 'fas fa-comments')
        ;

        $openAiMenu->addChild('消息记录')
            ->setUri($this->linkGenerator->getCurdListPage(Message::class))
            ->setAttribute('icon', 'fas fa-message')
        ;
    }
}
