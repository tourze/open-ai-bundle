<?php

namespace OpenAIBundle\Tests\Entity;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use PHPUnit\Framework\TestCase;

class CharacterTest extends TestCase
{
    private Character $character;

    protected function setUp(): void
    {
        $this->character = new Character();
    }

    public function test_getId_returnsNullByDefault(): void
    {
        $this->assertNull($this->character->getId());
    }

    public function test_name_canBeSetAndRetrieved(): void
    {
        $name = 'AI Assistant';
        $this->character->setName($name);
        $this->assertEquals($name, $this->character->getName());
    }

    public function test_avatar_canBeSetAndRetrieved(): void
    {
        $avatar = '/path/to/avatar.jpg';
        $this->character->setAvatar($avatar);
        $this->assertEquals($avatar, $this->character->getAvatar());
    }

    public function test_avatar_returnsNullByDefault(): void
    {
        $this->assertNull($this->character->getAvatar());
    }

    public function test_description_canBeSetAndRetrieved(): void
    {
        $description = 'A helpful AI assistant';
        $this->character->setDescription($description);
        $this->assertEquals($description, $this->character->getDescription());
    }

    public function test_description_returnsNullByDefault(): void
    {
        $this->assertNull($this->character->getDescription());
    }

    public function test_systemPrompt_canBeSetAndRetrieved(): void
    {
        $prompt = 'You are a helpful assistant.';
        $this->character->setSystemPrompt($prompt);
        $this->assertEquals($prompt, $this->character->getSystemPrompt());
    }

    public function test_temperature_canBeSetAndRetrieved(): void
    {
        $temperature = 0.8;
        $this->character->setTemperature($temperature);
        $this->assertEquals($temperature, $this->character->getTemperature());
    }

    public function test_temperature_defaultsToOne(): void
    {
        $this->assertEquals(1, $this->character->getTemperature());
    }

    public function test_topP_canBeSetAndRetrieved(): void
    {
        $topP = 0.9;
        $this->character->setTopP($topP);
        $this->assertEquals($topP, $this->character->getTopP());
    }

    public function test_topP_defaultsToZeroPointSeven(): void
    {
        $this->assertEquals(0.7, $this->character->getTopP());
    }

    public function test_maxTokens_canBeSetAndRetrieved(): void
    {
        $maxTokens = 4000;
        $this->character->setMaxTokens($maxTokens);
        $this->assertEquals($maxTokens, $this->character->getMaxTokens());
    }

    public function test_maxTokens_defaultsToTwoThousand(): void
    {
        $this->assertEquals(2000, $this->character->getMaxTokens());
    }

    public function test_presencePenalty_canBeSetAndRetrieved(): void
    {
        $penalty = 0.5;
        $this->character->setPresencePenalty($penalty);
        $this->assertEquals($penalty, $this->character->getPresencePenalty());
    }

    public function test_presencePenalty_defaultsToZero(): void
    {
        $this->assertEquals(0.0, $this->character->getPresencePenalty());
    }

    public function test_frequencyPenalty_canBeSetAndRetrieved(): void
    {
        $penalty = 0.3;
        $this->character->setFrequencyPenalty($penalty);
        $this->assertEquals($penalty, $this->character->getFrequencyPenalty());
    }

    public function test_frequencyPenalty_defaultsToZero(): void
    {
        $this->assertEquals(0.0, $this->character->getFrequencyPenalty());
    }

    public function test_preferredApiKey_canBeSetAndRetrieved(): void
    {
        $apiKey = $this->createMock(ApiKey::class);
        $this->character->setPreferredApiKey($apiKey);
        $this->assertSame($apiKey, $this->character->getPreferredApiKey());
    }

    public function test_preferredApiKey_returnsNullByDefault(): void
    {
        $this->assertNull($this->character->getPreferredApiKey());
    }

    public function test_supportFunctions_canBeSetAndRetrieved(): void
    {
        $functions = ['function1', 'function2'];
        $this->character->setSupportFunctions($functions);
        $this->assertEquals($functions, $this->character->getSupportFunctions());
    }

    public function test_supportFunctions_defaultsToEmptyArray(): void
    {
        $this->assertEquals([], $this->character->getSupportFunctions());
    }

    public function test_valid_canBeSetAndRetrieved(): void
    {
        $this->character->setValid(true);
        $this->assertTrue($this->character->isValid());

        $this->character->setValid(false);
        $this->assertFalse($this->character->isValid());
    }

    public function test_valid_defaultsToFalse(): void
    {
        $this->assertFalse($this->character->isValid());
    }

    public function test_createTime_canBeSetAndRetrieved(): void
    {
        $now = new \DateTimeImmutable();
        $this->character->setCreateTime($now);
        $this->assertEquals($now, $this->character->getCreateTime());
    }

    public function test_updateTime_canBeSetAndRetrieved(): void
    {
        $now = new \DateTimeImmutable();
        $this->character->setUpdateTime($now);
        $this->assertEquals($now, $this->character->getUpdateTime());
    }

    public function test_conversations_collection_isEmptyByDefault(): void
    {
        $this->assertCount(0, $this->character->getConversations());
    }

    public function test_addConversation_addsConversationToCollection(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->expects($this->once())
                    ->method('setActor')
                    ->with($this->character);

        $this->character->addConversation($conversation);
        $this->assertCount(1, $this->character->getConversations());
        $this->assertTrue($this->character->getConversations()->contains($conversation));
    }

    public function test_addConversation_doesNotAddDuplicateConversation(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->expects($this->once())
                    ->method('setActor')
                    ->with($this->character);

        $this->character->addConversation($conversation);
        $this->character->addConversation($conversation); // 添加相同对话

        $this->assertCount(1, $this->character->getConversations());
    }

    public function test_removeConversation_removesConversationFromCollection(): void
    {
        // 创建真实的Conversation实例用于测试
        $conversation = new Conversation();
        $conversation->setTitle('Test conversation');
        $conversation->setModel('test-model');
        $conversation->setValid(true);
        
        // 添加对话
        $this->character->addConversation($conversation);
        $this->assertCount(1, $this->character->getConversations());
        $this->assertTrue($this->character->getConversations()->contains($conversation));
        
        // 移除对话
        $this->character->removeConversation($conversation);
        $this->assertCount(0, $this->character->getConversations());
        $this->assertFalse($this->character->getConversations()->contains($conversation));
        
        // 验证conversation的actor已被设置为null
        $this->assertNull($conversation->getActor());
    }

    public function test_toString_returnsEmptyStringWhenNoId(): void
    {
        $this->assertEquals('', (string) $this->character);
    }

    public function test_toString_returnsNameWhenIdExists(): void
    {
        $name = 'Test Character';
        $this->character->setName($name);

        // 通过反射设置 ID
        $reflection = new \ReflectionClass($this->character);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->character, '12345');

        $this->assertEquals($name, (string) $this->character);
    }

    public function test_temperature_acceptsBoundaryValues(): void
    {
        // 测试边界值
        $this->character->setTemperature(0.0);
        $this->assertEquals(0.0, $this->character->getTemperature());

        $this->character->setTemperature(1.0);
        $this->assertEquals(1.0, $this->character->getTemperature());

        $this->character->setTemperature(2.0);
        $this->assertEquals(2.0, $this->character->getTemperature());
    }

    public function test_topP_acceptsBoundaryValues(): void
    {
        // 测试边界值
        $this->character->setTopP(0.0);
        $this->assertEquals(0.0, $this->character->getTopP());

        $this->character->setTopP(1.0);
        $this->assertEquals(1.0, $this->character->getTopP());
    }

    public function test_maxTokens_acceptsPositiveValues(): void
    {
        $values = [1, 100, 1000, 4096, 8192];

        foreach ($values as $value) {
            $this->character->setMaxTokens($value);
            $this->assertEquals($value, $this->character->getMaxTokens());
        }
    }

    public function test_presencePenalty_acceptsBoundaryValues(): void
    {
        // 测试范围 [-2.0, 2.0]
        $this->character->setPresencePenalty(-2.0);
        $this->assertEquals(-2.0, $this->character->getPresencePenalty());

        $this->character->setPresencePenalty(2.0);
        $this->assertEquals(2.0, $this->character->getPresencePenalty());
    }

    public function test_frequencyPenalty_acceptsBoundaryValues(): void
    {
        // 测试范围 [-2.0, 2.0]
        $this->character->setFrequencyPenalty(-2.0);
        $this->assertEquals(-2.0, $this->character->getFrequencyPenalty());

        $this->character->setFrequencyPenalty(2.0);
        $this->assertEquals(2.0, $this->character->getFrequencyPenalty());
    }

    public function test_supportFunctions_acceptsEmptyArray(): void
    {
        $this->character->setSupportFunctions([]);
        $this->assertEquals([], $this->character->getSupportFunctions());
    }

    public function test_supportFunctions_acceptsMultipleFunctions(): void
    {
        $functions = [
            'GetServerRandomNumber',
            'GetTableList',
            'FetchSqlResult',
            'ReadTextFile'
        ];

        $this->character->setSupportFunctions($functions);
        $this->assertEquals($functions, $this->character->getSupportFunctions());
    }

    public function test_systemPrompt_acceptsLongText(): void
    {
        $longPrompt = str_repeat('You are a helpful assistant. ', 100);
        $this->character->setSystemPrompt($longPrompt);
        $this->assertEquals($longPrompt, $this->character->getSystemPrompt());
    }

    public function test_description_acceptsEmptyString(): void
    {
        $this->character->setDescription('');
        $this->assertEquals('', $this->character->getDescription());
    }
} 