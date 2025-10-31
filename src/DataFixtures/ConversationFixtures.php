<?php

namespace OpenAIBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;

/**
 * AI对话数据装置 - 用于生成测试和开发用的对话数据
 */
class ConversationFixtures extends Fixture implements DependentFixtureInterface
{
    public const GENERAL_CHAT_REFERENCE = 'general-chat-conversation';
    public const TRANSLATION_TASK_REFERENCE = 'translation-task-conversation';
    public const PROGRAMMING_HELP_REFERENCE = 'programming-help-conversation';
    public const LEARNING_SESSION_REFERENCE = 'learning-session-conversation';

    public function load(ObjectManager $manager): void
    {
        // 创建通用聊天对话
        $generalChat = new Conversation();
        $generalChat->setValid(true);
        $generalChat->setTitle('日常问答对话');
        $generalChat->setDescription('用户与AI助手的日常问答对话');
        $generalChat->setModel('gpt-3.5-turbo');
        $generalChat->setSystemPrompt('你是一位友善的AI助手，请用简洁、准确的语言回答用户的问题。');
        $generalChat->setActor($this->getReference(CharacterFixtures::ASSISTANT_CHARACTER_REFERENCE, Character::class));

        $manager->persist($generalChat);
        $this->addReference(self::GENERAL_CHAT_REFERENCE, $generalChat);

        // 创建翻译任务对话
        $translationTask = new Conversation();
        $translationTask->setValid(true);
        $translationTask->setTitle('中英文翻译任务');
        $translationTask->setDescription('专业的中英文翻译对话');
        $translationTask->setModel('gpt-4');
        $translationTask->setSystemPrompt('你是一位专业的翻译专家，请准确翻译用户提供的文本。');
        $translationTask->setActor($this->getReference(CharacterFixtures::TRANSLATOR_CHARACTER_REFERENCE, Character::class));

        $manager->persist($translationTask);
        $this->addReference(self::TRANSLATION_TASK_REFERENCE, $translationTask);

        // 创建编程帮助对话
        $programmingHelp = new Conversation();
        $programmingHelp->setValid(true);
        $programmingHelp->setTitle('PHP开发技术咨询');
        $programmingHelp->setDescription('关于PHP开发的技术问题讨论');
        $programmingHelp->setModel('gpt-4');
        $programmingHelp->setSystemPrompt('你是一位经验丰富的PHP开发专家，请提供专业的技术建议和代码解决方案。');
        $programmingHelp->setActor($this->getReference(CharacterFixtures::PROGRAMMER_CHARACTER_REFERENCE, Character::class));

        $manager->persist($programmingHelp);
        $this->addReference(self::PROGRAMMING_HELP_REFERENCE, $programmingHelp);

        // 创建学习辅导对话
        $learningSession = new Conversation();
        $learningSession->setValid(true);
        $learningSession->setTitle('机器学习概念讲解');
        $learningSession->setDescription('关于机器学习基础概念的教学对话');
        $learningSession->setModel('claude-3-sonnet-20240229');
        $learningSession->setSystemPrompt('你是一位耐心的教师，请用简单易懂的方式解释机器学习的相关概念。');
        $learningSession->setActor($this->getReference(CharacterFixtures::TEACHER_CHARACTER_REFERENCE, Character::class));

        $manager->persist($learningSession);
        $this->addReference(self::LEARNING_SESSION_REFERENCE, $learningSession);

        // 创建一些额外的对话用于测试
        $demoChat1 = new Conversation();
        $demoChat1->setValid(true);
        $demoChat1->setTitle('旅行规划咨询');
        $demoChat1->setDescription('关于旅行规划的咨询对话');
        $demoChat1->setModel('gpt-3.5-turbo');
        $demoChat1->setSystemPrompt('你是一位旅行规划专家，请为用户提供详细的旅行建议。');
        $demoChat1->setActor($this->getReference(CharacterFixtures::ASSISTANT_CHARACTER_REFERENCE, Character::class));

        $manager->persist($demoChat1);

        $demoChat2 = new Conversation();
        $demoChat2->setValid(true);
        $demoChat2->setTitle('代码审查讨论');
        $demoChat2->setDescription('关于代码审查和优化的技术讨论');
        $demoChat2->setModel('gpt-4');
        $demoChat2->setSystemPrompt('你是一位资深的代码审查专家，请提供详细的代码改进建议。');
        $demoChat2->setActor($this->getReference(CharacterFixtures::PROGRAMMER_CHARACTER_REFERENCE, Character::class));

        $manager->persist($demoChat2);

        // 创建一个无效的对话用于测试
        $inactiveConversation = new Conversation();
        $inactiveConversation->setValid(false);
        $inactiveConversation->setTitle('已停用的测试对话');
        $inactiveConversation->setDescription('这是一个用于测试的停用对话');
        $inactiveConversation->setModel('gpt-3.5-turbo');
        $inactiveConversation->setSystemPrompt('这是一个测试用的停用对话');
        $inactiveConversation->setActor($this->getReference(CharacterFixtures::ASSISTANT_CHARACTER_REFERENCE, Character::class));

        $manager->persist($inactiveConversation);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CharacterFixtures::class,
        ];
    }
}
