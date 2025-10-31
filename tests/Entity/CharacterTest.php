<?php

namespace OpenAIBundle\Tests\Entity;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Character::class)]
final class CharacterTest extends AbstractEntityTestCase
{
    private ?Character $character = null;

    private function getCharacter(): Character
    {
        return $this->character ??= new Character();
    }

    protected function onSetUp(): void
    {
    }

    protected function createEntity(): object
    {
        return new Character();
    }

    /** @return iterable<string, array{0: string, 1: mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'AI Assistant'];
        yield 'avatar' => ['avatar', '/path/to/avatar.jpg'];
        yield 'description' => ['description', 'A helpful AI assistant'];
        yield 'systemPrompt' => ['systemPrompt', 'You are a helpful assistant.'];
        yield 'temperature' => ['temperature', 0.8];
        yield 'topP' => ['topP', 0.9];
        yield 'maxTokens' => ['maxTokens', 4000];
        yield 'presencePenalty' => ['presencePenalty', 0.5];
        yield 'frequencyPenalty' => ['frequencyPenalty', 0.3];
        yield 'supportFunctions' => ['supportFunctions', ['function1', 'function2']];
        yield 'valid' => ['valid', true];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $this->assertNull($this->getCharacter()->getId());
    }

    public function testNameCanBeSetAndRetrieved(): void
    {
        $name = 'AI Assistant';
        $this->getCharacter()->setName($name);
        $this->assertEquals($name, $this->getCharacter()->getName());
    }

    public function testAvatarCanBeSetAndRetrieved(): void
    {
        $avatar = '/path/to/avatar.jpg';
        $this->getCharacter()->setAvatar($avatar);
        $this->assertEquals($avatar, $this->getCharacter()->getAvatar());
    }

    public function testAvatarReturnsNullByDefault(): void
    {
        $this->assertNull($this->getCharacter()->getAvatar());
    }

    public function testDescriptionCanBeSetAndRetrieved(): void
    {
        $description = 'A helpful AI assistant';
        $this->getCharacter()->setDescription($description);
        $this->assertEquals($description, $this->getCharacter()->getDescription());
    }

    public function testDescriptionReturnsNullByDefault(): void
    {
        $this->assertNull($this->getCharacter()->getDescription());
    }

    public function testSystemPromptCanBeSetAndRetrieved(): void
    {
        $prompt = 'You are a helpful assistant.';
        $this->getCharacter()->setSystemPrompt($prompt);
        $this->assertEquals($prompt, $this->getCharacter()->getSystemPrompt());
    }

    public function testTemperatureCanBeSetAndRetrieved(): void
    {
        $temperature = 0.8;
        $this->getCharacter()->setTemperature($temperature);
        $this->assertEquals($temperature, $this->getCharacter()->getTemperature());
    }

    public function testTemperatureDefaultsToOne(): void
    {
        $this->assertEquals(1, $this->getCharacter()->getTemperature());
    }

    public function testTopPCanBeSetAndRetrieved(): void
    {
        $topP = 0.9;
        $this->getCharacter()->setTopP($topP);
        $this->assertEquals($topP, $this->getCharacter()->getTopP());
    }

    public function testTopPDefaultsToZeroPointSeven(): void
    {
        $this->assertEquals(0.7, $this->getCharacter()->getTopP());
    }

    public function testMaxTokensCanBeSetAndRetrieved(): void
    {
        $maxTokens = 4000;
        $this->getCharacter()->setMaxTokens($maxTokens);
        $this->assertEquals($maxTokens, $this->getCharacter()->getMaxTokens());
    }

    public function testMaxTokensDefaultsToTwoThousand(): void
    {
        $this->assertEquals(2000, $this->getCharacter()->getMaxTokens());
    }

    public function testPresencePenaltyCanBeSetAndRetrieved(): void
    {
        $penalty = 0.5;
        $this->getCharacter()->setPresencePenalty($penalty);
        $this->assertEquals($penalty, $this->getCharacter()->getPresencePenalty());
    }

    public function testPresencePenaltyDefaultsToZero(): void
    {
        $this->assertEquals(0.0, $this->getCharacter()->getPresencePenalty());
    }

    public function testFrequencyPenaltyCanBeSetAndRetrieved(): void
    {
        $penalty = 0.3;
        $this->getCharacter()->setFrequencyPenalty($penalty);
        $this->assertEquals($penalty, $this->getCharacter()->getFrequencyPenalty());
    }

    public function testFrequencyPenaltyDefaultsToZero(): void
    {
        $this->assertEquals(0.0, $this->getCharacter()->getFrequencyPenalty());
    }

    public function testPreferredApiKeyCanBeSetAndRetrieved(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 Doctrine Entity 实体类，作为 Character 的首选 API 键
         * 2. 这种使用是合理和必要的，因为测试的是实体间的关联关系
         * 3. 暂无更好的替代方案，因为需要验证对象引用的一致性
         */
        $apiKey = $this->createMock(ApiKey::class);
        $this->getCharacter()->setPreferredApiKey($apiKey);
        $this->assertSame($apiKey, $this->getCharacter()->getPreferredApiKey());
    }

    public function testPreferredApiKeyReturnsNullByDefault(): void
    {
        $this->assertNull($this->getCharacter()->getPreferredApiKey());
    }

    public function testSupportFunctionsCanBeSetAndRetrieved(): void
    {
        $functions = ['function1', 'function2'];
        $this->getCharacter()->setSupportFunctions($functions);
        $this->assertEquals($functions, $this->getCharacter()->getSupportFunctions());
    }

    public function testSupportFunctionsDefaultsToEmptyArray(): void
    {
        $this->assertEquals([], $this->getCharacter()->getSupportFunctions());
    }

    public function testValidCanBeSetAndRetrieved(): void
    {
        $this->getCharacter()->setValid(true);
        $this->assertTrue($this->getCharacter()->isValid());

        $this->getCharacter()->setValid(false);
        $this->assertFalse($this->getCharacter()->isValid());
    }

    public function testValidDefaultsToFalse(): void
    {
        $this->assertFalse($this->getCharacter()->isValid());
    }

    public function testCreateTimeCanBeSetAndRetrieved(): void
    {
        $now = new \DateTimeImmutable();
        $this->getCharacter()->setCreateTime($now);
        $this->assertEquals($now, $this->getCharacter()->getCreateTime());
    }

    public function testUpdateTimeCanBeSetAndRetrieved(): void
    {
        $now = new \DateTimeImmutable();
        $this->getCharacter()->setUpdateTime($now);
        $this->assertEquals($now, $this->getCharacter()->getUpdateTime());
    }

    public function testConversationsCollectionIsEmptyByDefault(): void
    {
        $this->assertCount(0, $this->getCharacter()->getConversations());
    }

    public function testAddConversationAddsConversationToCollection(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，需要测试与 Character 的关联关系
         * 2. 这种使用是合理和必要的，因为测试的是实体间的双向关联操作
         * 3. 暂无更好的替代方案，因为需要验证 setActor 方法的调用行为
         */
        $conversation = $this->createMock(Conversation::class);
        $conversation->expects($this->once())
            ->method('setActor')
            ->with($this->getCharacter())
        ;

        $this->getCharacter()->addConversation($conversation);
        $this->assertCount(1, $this->getCharacter()->getConversations());
        $this->assertTrue($this->getCharacter()->getConversations()->contains($conversation));
    }

    public function testAddConversationDoesNotAddDuplicateConversation(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，需要测试重复添加对话的逻辑
         * 2. 这种使用是合理和必要的，因为测试的是集合操作和去重逻辑
         * 3. 暂无更好的替代方案，因为需要验证 setActor 方法的调用次数
         */
        $conversation = $this->createMock(Conversation::class);
        $conversation->expects($this->once())
            ->method('setActor')
            ->with($this->getCharacter())
        ;

        $this->getCharacter()->addConversation($conversation);
        $this->getCharacter()->addConversation($conversation); // 添加相同对话

        $this->assertCount(1, $this->getCharacter()->getConversations());
    }

    public function testRemoveConversationRemovesConversationFromCollection(): void
    {
        // 创建真实的Conversation实例用于测试
        $conversation = new Conversation();
        $conversation->setTitle('Test conversation');
        $conversation->setModel('test-model');
        $conversation->setValid(true);

        // 添加对话
        $this->getCharacter()->addConversation($conversation);
        $this->assertCount(1, $this->getCharacter()->getConversations());
        $this->assertTrue($this->getCharacter()->getConversations()->contains($conversation));

        // 移除对话
        $this->getCharacter()->removeConversation($conversation);
        $this->assertCount(0, $this->getCharacter()->getConversations());
        $this->assertFalse($this->getCharacter()->getConversations()->contains($conversation));

        // 验证conversation的actor已被设置为null
        $this->assertNull($conversation->getActor());
    }

    public function testToStringReturnsEmptyStringWhenNoId(): void
    {
        $this->assertEquals('', (string) $this->getCharacter());
    }

    public function testToStringReturnsNameWhenIdExists(): void
    {
        $name = 'Test Character';
        $this->getCharacter()->setName($name);

        // 通过反射设置 ID
        $reflection = new \ReflectionClass($this->getCharacter());
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->getCharacter(), '12345');

        $this->assertEquals($name, (string) $this->getCharacter());
    }

    public function testTemperatureAcceptsBoundaryValues(): void
    {
        // 测试边界值
        $this->getCharacter()->setTemperature(0.0);
        $this->assertEquals(0.0, $this->getCharacter()->getTemperature());

        $this->getCharacter()->setTemperature(1.0);
        $this->assertEquals(1.0, $this->getCharacter()->getTemperature());

        $this->getCharacter()->setTemperature(2.0);
        $this->assertEquals(2.0, $this->getCharacter()->getTemperature());
    }

    public function testTopPAcceptsBoundaryValues(): void
    {
        // 测试边界值
        $this->getCharacter()->setTopP(0.0);
        $this->assertEquals(0.0, $this->getCharacter()->getTopP());

        $this->getCharacter()->setTopP(1.0);
        $this->assertEquals(1.0, $this->getCharacter()->getTopP());
    }

    public function testMaxTokensAcceptsPositiveValues(): void
    {
        $values = [1, 100, 1000, 4096, 8192];

        foreach ($values as $value) {
            $this->getCharacter()->setMaxTokens($value);
            $this->assertEquals($value, $this->getCharacter()->getMaxTokens());
        }
    }

    public function testPresencePenaltyAcceptsBoundaryValues(): void
    {
        // 测试范围 [-2.0, 2.0]
        $this->getCharacter()->setPresencePenalty(-2.0);
        $this->assertEquals(-2.0, $this->getCharacter()->getPresencePenalty());

        $this->getCharacter()->setPresencePenalty(2.0);
        $this->assertEquals(2.0, $this->getCharacter()->getPresencePenalty());
    }

    public function testFrequencyPenaltyAcceptsBoundaryValues(): void
    {
        // 测试范围 [-2.0, 2.0]
        $this->getCharacter()->setFrequencyPenalty(-2.0);
        $this->assertEquals(-2.0, $this->getCharacter()->getFrequencyPenalty());

        $this->getCharacter()->setFrequencyPenalty(2.0);
        $this->assertEquals(2.0, $this->getCharacter()->getFrequencyPenalty());
    }

    public function testSupportFunctionsAcceptsEmptyArray(): void
    {
        $this->getCharacter()->setSupportFunctions([]);
        $this->assertEquals([], $this->getCharacter()->getSupportFunctions());
    }

    public function testSupportFunctionsAcceptsMultipleFunctions(): void
    {
        $functions = [
            'GetServerRandomNumber',
            'GetTableList',
            'FetchSqlResult',
            'ReadTextFile',
        ];

        $this->getCharacter()->setSupportFunctions($functions);
        $this->assertEquals($functions, $this->getCharacter()->getSupportFunctions());
    }

    public function testSystemPromptAcceptsLongText(): void
    {
        $longPrompt = str_repeat('You are a helpful assistant. ', 100);
        $this->getCharacter()->setSystemPrompt($longPrompt);
        $this->assertEquals($longPrompt, $this->getCharacter()->getSystemPrompt());
    }

    public function testDescriptionAcceptsEmptyString(): void
    {
        $this->getCharacter()->setDescription('');
        $this->assertEquals('', $this->getCharacter()->getDescription());
    }
}
