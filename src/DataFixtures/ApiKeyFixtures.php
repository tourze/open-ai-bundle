<?php

namespace OpenAIBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Enum\ContextLength;

/**
 * API密钥数据装置 - 用于生成测试和开发用的API密钥数据
 */
class ApiKeyFixtures extends Fixture
{
    public const GPT_35_TURBO_KEY_REFERENCE = 'gpt-35-turbo-key';
    public const GPT_4_KEY_REFERENCE = 'gpt-4-key';
    public const CLAUDE_KEY_REFERENCE = 'claude-key';

    public function load(ObjectManager $manager): void
    {
        // 创建 GPT-3.5-turbo API密钥
        $gpt35Key = new ApiKey();
        $gpt35Key->setValid(true);
        $gpt35Key->setTitle('GPT-3.5 Turbo 主要密钥');
        $gpt35Key->setApiKey('sk-test-gpt35-turbo-key-12345678901234567890123456789012');
        $gpt35Key->setModel('gpt-3.5-turbo');
        $gpt35Key->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $gpt35Key->setFunctionCalling(true);
        $gpt35Key->setContextLength(ContextLength::K_16);

        $manager->persist($gpt35Key);
        $this->addReference(self::GPT_35_TURBO_KEY_REFERENCE, $gpt35Key);

        // 创建 GPT-4 API密钥
        $gpt4Key = new ApiKey();
        $gpt4Key->setValid(true);
        $gpt4Key->setTitle('GPT-4 高级密钥');
        $gpt4Key->setApiKey('sk-test-gpt4-key-abcdefghijklmnopqrstuvwxyz123456');
        $gpt4Key->setModel('gpt-4');
        $gpt4Key->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $gpt4Key->setFunctionCalling(true);
        $gpt4Key->setContextLength(ContextLength::K_128);

        $manager->persist($gpt4Key);
        $this->addReference(self::GPT_4_KEY_REFERENCE, $gpt4Key);

        // 创建 Claude API密钥
        $claudeKey = new ApiKey();
        $claudeKey->setValid(true);
        $claudeKey->setTitle('Claude-3 Sonnet 密钥');
        $claudeKey->setApiKey('sk-ant-test-claude-key-987654321abcdef0123456789abcdef0');
        $claudeKey->setModel('claude-3-sonnet-20240229');
        $claudeKey->setChatCompletionUrl('https://api.anthropic.com/v1/messages');
        $claudeKey->setFunctionCalling(true);
        $claudeKey->setContextLength(ContextLength::K_64);

        $manager->persist($claudeKey);
        $this->addReference(self::CLAUDE_KEY_REFERENCE, $claudeKey);

        // 创建一个无效的API密钥用于测试
        $invalidKey = new ApiKey();
        $invalidKey->setValid(false);
        $invalidKey->setTitle('失效的测试密钥');
        $invalidKey->setApiKey('sk-invalid-key-1234567890abcdef1234567890abcdef');
        $invalidKey->setModel('gpt-3.5-turbo');
        $invalidKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $invalidKey->setFunctionCalling(false);
        $invalidKey->setContextLength(ContextLength::K_4);

        $manager->persist($invalidKey);

        $manager->flush();
    }
}
