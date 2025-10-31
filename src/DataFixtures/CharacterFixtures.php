<?php

namespace OpenAIBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;

/**
 * AI角色设定数据装置 - 用于生成测试和开发用的AI角色数据
 */
class CharacterFixtures extends Fixture implements DependentFixtureInterface
{
    public const ASSISTANT_CHARACTER_REFERENCE = 'assistant-character';
    public const TRANSLATOR_CHARACTER_REFERENCE = 'translator-character';
    public const PROGRAMMER_CHARACTER_REFERENCE = 'programmer-character';
    public const TEACHER_CHARACTER_REFERENCE = 'teacher-character';

    public function load(ObjectManager $manager): void
    {
        // 创建通用助手角色
        $assistant = new Character();
        $assistant->setValid(true);
        $assistant->setName('智能助手');
        $assistant->setAvatar('https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=200&h=200&fit=crop&crop=face');
        $assistant->setDescription('一个友善、专业的AI助手，能够帮助用户解答各种问题');
        $assistant->setSystemPrompt('你是一位友善、专业的AI助手。请用简洁、准确的语言回答用户的问题，并尽力提供有用的帮助。');
        $assistant->setTemperature(0.7);
        $assistant->setTopP(0.9);
        $assistant->setMaxTokens(2000);
        $assistant->setPresencePenalty(0.0);
        $assistant->setFrequencyPenalty(0.0);
        $assistant->setPreferredApiKey($this->getReference(ApiKeyFixtures::GPT_35_TURBO_KEY_REFERENCE, ApiKey::class));
        $assistant->setSupportFunctions(['get_weather', 'calculate', 'search_web']);

        $manager->persist($assistant);
        $this->addReference(self::ASSISTANT_CHARACTER_REFERENCE, $assistant);

        // 创建翻译专家角色
        $translator = new Character();
        $translator->setValid(true);
        $translator->setName('翻译专家');
        $translator->setAvatar('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop&crop=face');
        $translator->setDescription('专业的多语言翻译专家，精通中英文及其他多种语言的互译');
        $translator->setSystemPrompt('你是一位专业的翻译专家，精通多种语言。请准确、流畅地翻译用户提供的文本，保持原文的语境和语气。对于专业术语，请提供最恰当的翻译。');
        $translator->setTemperature(0.3);
        $translator->setTopP(0.8);
        $translator->setMaxTokens(3000);
        $translator->setPresencePenalty(0.1);
        $translator->setFrequencyPenalty(0.1);
        $translator->setPreferredApiKey($this->getReference(ApiKeyFixtures::GPT_4_KEY_REFERENCE, ApiKey::class));
        $translator->setSupportFunctions(['translate_text', 'detect_language']);

        $manager->persist($translator);
        $this->addReference(self::TRANSLATOR_CHARACTER_REFERENCE, $translator);

        // 创建编程助手角色
        $programmer = new Character();
        $programmer->setValid(true);
        $programmer->setName('编程助手');
        $programmer->setAvatar('https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=200&h=200&fit=crop&crop=face');
        $programmer->setDescription('专业的编程助手，精通多种编程语言，能够帮助解决代码问题和提供技术建议');
        $programmer->setSystemPrompt('你是一位经验丰富的编程专家，精通多种编程语言和技术栈。请提供准确、实用的代码解决方案，并解释代码的工作原理。注重代码质量、最佳实践和性能优化。');
        $programmer->setTemperature(0.2);
        $programmer->setTopP(0.7);
        $programmer->setMaxTokens(4000);
        $programmer->setPresencePenalty(0.0);
        $programmer->setFrequencyPenalty(0.2);
        $programmer->setPreferredApiKey($this->getReference(ApiKeyFixtures::GPT_4_KEY_REFERENCE, ApiKey::class));
        $programmer->setSupportFunctions(['analyze_code', 'debug_code', 'generate_code', 'explain_algorithm']);

        $manager->persist($programmer);
        $this->addReference(self::PROGRAMMER_CHARACTER_REFERENCE, $programmer);

        // 创建教师角色
        $teacher = new Character();
        $teacher->setValid(true);
        $teacher->setName('知识导师');
        $teacher->setAvatar('https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=200&h=200&fit=crop&crop=face');
        $teacher->setDescription('耐心的知识导师，擅长用简单易懂的方式解释复杂概念');
        $teacher->setSystemPrompt('你是一位耐心、友善的教师。请用简单易懂的语言解释复杂的概念，通过举例和类比帮助学生理解。鼓励学生提问，并循序渐进地引导学习。');
        $teacher->setTemperature(0.8);
        $teacher->setTopP(0.9);
        $teacher->setMaxTokens(2500);
        $teacher->setPresencePenalty(0.3);
        $teacher->setFrequencyPenalty(0.0);
        $teacher->setPreferredApiKey($this->getReference(ApiKeyFixtures::CLAUDE_KEY_REFERENCE, ApiKey::class));
        $teacher->setSupportFunctions(['explain_concept', 'create_example', 'generate_quiz']);

        $manager->persist($teacher);
        $this->addReference(self::TEACHER_CHARACTER_REFERENCE, $teacher);

        // 创建一个无效的角色用于测试
        $inactiveCharacter = new Character();
        $inactiveCharacter->setValid(false);
        $inactiveCharacter->setName('停用的测试角色');
        $inactiveCharacter->setDescription('这是一个用于测试的停用角色');
        $inactiveCharacter->setSystemPrompt('这是一个测试用的停用角色');
        $inactiveCharacter->setTemperature(0.5);
        $inactiveCharacter->setTopP(0.5);
        $inactiveCharacter->setMaxTokens(1000);
        $inactiveCharacter->setPresencePenalty(0.0);
        $inactiveCharacter->setFrequencyPenalty(0.0);
        $inactiveCharacter->setSupportFunctions([]);

        $manager->persist($inactiveCharacter);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ApiKeyFixtures::class,
        ];
    }
}
