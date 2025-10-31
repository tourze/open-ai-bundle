<?php

namespace OpenAIBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * Conversation实体测试
 *
 * @internal
 */
#[CoversClass(Conversation::class)]
final class ConversationTest extends AbstractEntityTestCase
{
    private ?Conversation $conversation = null;

    protected function onSetUp(): void
    {
    }

    private function getConversation(): Conversation
    {
        return $this->conversation ??= new Conversation();
    }

    protected function createEntity(): object
    {
        return new Conversation();
    }

    /** @return iterable<string, array{0: string, 1: mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'title' => ['title', 'Test Conversation'];
        yield 'description' => ['description', 'Test description'];
        yield 'model' => ['model', 'gpt-4'];
        yield 'systemPrompt' => ['systemPrompt', 'You are a helpful assistant.'];
        yield 'valid' => ['valid', true];
        yield 'createdBy' => ['createdBy', 'user123'];
        yield 'updatedBy' => ['updatedBy', 'admin456'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testConstructorInitializesMessagesCollection(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->getConversation()->getMessages());
        $this->assertCount(0, $this->getConversation()->getMessages());
    }

    public function testStringRepresentationReturnsTitle(): void
    {
        $title = '测试对话';
        $this->getConversation()->setTitle($title);

        $this->assertEquals($title, (string) $this->getConversation());
    }

    public function testTitleGetterAndSetter(): void
    {
        $title = 'AI聊天对话';
        $this->getConversation()->setTitle($title);

        $this->assertEquals($title, $this->getConversation()->getTitle());
    }

    public function testTitleSetterWithEmptyString(): void
    {
        $this->getConversation()->setTitle('');

        $this->assertEquals('', $this->getConversation()->getTitle());
    }

    public function testTitleSetterWithSpecialCharacters(): void
    {
        $title = '特殊字符测试 #@$% 🤖';
        $this->getConversation()->setTitle($title);

        $this->assertEquals($title, $this->getConversation()->getTitle());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $description = '这是一个测试对话的描述';
        $this->getConversation()->setDescription($description);

        $this->assertEquals($description, $this->getConversation()->getDescription());
    }

    public function testDescriptionSetterWithNull(): void
    {
        $this->getConversation()->setDescription(null);

        $this->assertNull($this->getConversation()->getDescription());
    }

    public function testDescriptionSetterWithLongText(): void
    {
        $longText = str_repeat('测试文本', 1000);
        $this->getConversation()->setDescription($longText);

        $this->assertEquals($longText, $this->getConversation()->getDescription());
    }

    public function testModelGetterAndSetter(): void
    {
        $model = 'gpt-4';
        $this->getConversation()->setModel($model);

        $this->assertEquals($model, $this->getConversation()->getModel());
    }

    public function testModelDefaultValue(): void
    {
        $this->assertEquals('gpt-3.5-turbo', $this->getConversation()->getModel());
    }

    public function testModelSetterWithDifferentModels(): void
    {
        $models = ['gpt-4', 'gpt-3.5-turbo', 'claude-2'];

        foreach ($models as $model) {
            $this->getConversation()->setModel($model);
            $this->assertEquals($model, $this->getConversation()->getModel());
        }
    }

    public function testSystemPromptGetterAndSetter(): void
    {
        $prompt = '你是一个友好的AI助手';
        $this->getConversation()->setSystemPrompt($prompt);

        $this->assertEquals($prompt, $this->getConversation()->getSystemPrompt());
    }

    public function testSystemPromptSetterWithNull(): void
    {
        $this->getConversation()->setSystemPrompt(null);

        $this->assertNull($this->getConversation()->getSystemPrompt());
    }

    public function testSystemPromptSetterWithLongPrompt(): void
    {
        $longPrompt = str_repeat('系统提示词测试', 500);
        $this->getConversation()->setSystemPrompt($longPrompt);

        $this->assertEquals($longPrompt, $this->getConversation()->getSystemPrompt());
    }

    public function testAddMessage(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，需要测试与 Conversation 的关联关系
         * 2. 这种使用是合理和必要的，因为测试的是实体间的双向关联操作
         * 3. 暂无更好的替代方案，因为需要验证 setConversation 方法的调用行为
         */
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('setConversation')
            ->with($this->getConversation())
        ;

        $this->getConversation()->addMessage($message);

        $this->assertTrue($this->getConversation()->getMessages()->contains($message));
        $this->assertCount(1, $this->getConversation()->getMessages());
    }

    public function testAddMessageDoesNotAddDuplicate(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，需要测试重复添加消息的逻辑
         * 2. 这种使用是合理和必要的，因为测试的是集合操作和去重逻辑
         * 3. 暂无更好的替代方案，因为需要验证 setConversation 方法的调用次数
         */
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('setConversation')
            ->with($this->getConversation())
        ;

        $this->getConversation()->addMessage($message);
        $this->getConversation()->addMessage($message); // 添加相同消息

        $this->assertCount(1, $this->getConversation()->getMessages());
    }

    public function testRemoveMessage(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，需要测试从对话中移除消息的逻辑
         * 2. 这种使用是合理和必要的，因为测试的是实体间的关联管理
         * 3. 暂无更好的替代方案，因为需要验证双向关联的清理行为
         */
        $message = $this->createMock(Message::class);
        $message->method('getConversation')->willReturn($this->getConversation());
        $message->expects($this->once())
            ->method('setConversation')
            ->with(null)
        ;

        $this->getConversation()->getMessages()->add($message);

        $this->getConversation()->removeMessage($message);

        $this->assertFalse($this->getConversation()->getMessages()->contains($message));
    }

    public function testRemoveMessageWhenNotInCollection(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，测试边界情况下的移除逻辑
         * 2. 这种使用是合理和必要的，因为需要验证不存在的消息不会被错误处理
         * 3. 暂无更好的替代方案，因为需要验证 setConversation 方法不被调用
         */
        $message = $this->createMock(Message::class);
        $message->expects($this->never())
            ->method('setConversation')
        ;

        $this->getConversation()->removeMessage($message);
    }

    public function testRemoveMessageWhenConversationNotSame(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，测试不同对话的消息移除逻辑
         * 2. 这种使用是合理和必要的，因为需要验证关联关系的一致性检查
         * 3. 暂无更好的替代方案，因为需要 mock 不同的对话实例进行比较
         */
        $message = $this->createMock(Message::class);
        $otherConversation = new Conversation();
        $message->method('getConversation')->willReturn($otherConversation);
        $message->expects($this->never())
            ->method('setConversation')
        ;

        $this->getConversation()->getMessages()->add($message);
        $this->getConversation()->removeMessage($message);

        $this->assertFalse($this->getConversation()->getMessages()->contains($message));
    }

    public function testClearMessages(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，需要测试清除所有消息的逻辑
         * 2. 这种使用是合理和必要的，因为测试的是批量清理关联关系
         * 3. 暂无更好的替代方案，因为需要验证多个消息的 setConversation 调用
         */
        $message1 = $this->createMock(Message::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，作为第二个消息测试清除功能
         * 2. 这种使用是合理和必要的，因为测试的是批量操作的完整性
         * 3. 暂无更好的替代方案，因为需要创建多个独立的消息实例
         */
        $message2 = $this->createMock(Message::class);

        $message1->method('getConversation')->willReturn($this->getConversation());
        $message2->method('getConversation')->willReturn($this->getConversation());

        $message1->expects($this->once())->method('setConversation')->with(null);
        $message2->expects($this->once())->method('setConversation')->with(null);

        $this->getConversation()->getMessages()->add($message1);
        $this->getConversation()->getMessages()->add($message2);

        $this->getConversation()->clearMessages();

        $this->assertCount(0, $this->getConversation()->getMessages());
    }

    public function testActorGetterAndSetter(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，作为 Conversation 的角色实体
         * 2. 这种使用是合理和必要的，因为测试的是实体间的关联关系
         * 3. 暂无更好的替代方案，因为需要验证对象引用的一致性
         */
        $character = $this->createMock(Character::class);
        $this->getConversation()->setActor($character);

        $this->assertSame($character, $this->getConversation()->getActor());
    }

    public function testActorSetterWithNull(): void
    {
        $this->getConversation()->setActor(null);

        $this->assertNull($this->getConversation()->getActor());
    }

    public function testValidGetterAndSetter(): void
    {
        $this->getConversation()->setValid(true);

        $this->assertTrue($this->getConversation()->isValid());
    }

    public function testValidDefaultValue(): void
    {
        $this->assertFalse($this->getConversation()->isValid());
    }

    public function testValidSetterWithNull(): void
    {
        $this->getConversation()->setValid(null);

        $this->assertNull($this->getConversation()->isValid());
    }

    public function testCreatedByGetterAndSetter(): void
    {
        $createdBy = 'user123';
        $this->getConversation()->setCreatedBy($createdBy);

        $this->assertEquals($createdBy, $this->getConversation()->getCreatedBy());
    }

    public function testCreatedBySetterWithNull(): void
    {
        $this->getConversation()->setCreatedBy(null);

        $this->assertNull($this->getConversation()->getCreatedBy());
    }

    public function testUpdatedByGetterAndSetter(): void
    {
        $updatedBy = 'admin456';
        $this->getConversation()->setUpdatedBy($updatedBy);

        $this->assertEquals($updatedBy, $this->getConversation()->getUpdatedBy());
    }

    public function testUpdatedBySetterWithNull(): void
    {
        $this->getConversation()->setUpdatedBy(null);

        $this->assertNull($this->getConversation()->getUpdatedBy());
    }

    public function testCreateTimeGetterAndSetter(): void
    {
        $dateTime = new \DateTimeImmutable('2023-01-01 12:00:00');
        $this->getConversation()->setCreateTime($dateTime);

        $this->assertSame($dateTime, $this->getConversation()->getCreateTime());
    }

    public function testCreateTimeSetterWithNull(): void
    {
        $this->getConversation()->setCreateTime(null);

        $this->assertNull($this->getConversation()->getCreateTime());
    }

    public function testUpdateTimeGetterAndSetter(): void
    {
        $dateTime = new \DateTimeImmutable('2023-01-02 15:30:00');
        $this->getConversation()->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $this->getConversation()->getUpdateTime());
    }

    public function testUpdateTimeSetterWithNull(): void
    {
        $this->getConversation()->setUpdateTime(null);

        $this->assertNull($this->getConversation()->getUpdateTime());
    }

    public function testGetIdReturnsNull(): void
    {
        $this->assertNull($this->getConversation()->getId());
    }

    public function testCompleteConversationWorkflow(): void
    {
        // 设置基本属性
        $this->getConversation()->setTitle('完整对话测试');
        $this->getConversation()->setDescription('测试完整对话流程');
        $this->getConversation()->setModel('gpt-4');
        $this->getConversation()->setSystemPrompt('你是一个测试助手');
        $this->getConversation()->setValid(true);

        // 设置角色
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，作为完整测试流程的一部分
         * 2. 这种使用是合理和必要的，因为测试的是完整的业务流程
         * 3. 暂无更好的替代方案，因为需要与其他组件配合测试
         */
        $character = $this->createMock(Character::class);
        $this->getConversation()->setActor($character);

        // 添加消息
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，作为完整测试流程的一部分
         * 2. 这种使用是合理和必要的，因为测试的是完整的业务流程
         * 3. 暂无更好的替代方案，因为需要验证消息与对话的关联关系
         */
        $message1 = $this->createMock(Message::class);
        $message1->method('getConversation')->willReturn($this->getConversation());
        $message1->expects($this->once())->method('setConversation')->with($this->getConversation());

        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，作为第二个消息测试完整流程
         * 2. 这种使用是合理和必要的，因为测试的是多个消息的关联管理
         * 3. 暂无更好的替代方案，因为需要创建多个独立的消息实例
         */
        $message2 = $this->createMock(Message::class);
        $message2->method('getConversation')->willReturn($this->getConversation());
        $message2->expects($this->once())->method('setConversation')->with($this->getConversation());

        $this->getConversation()->addMessage($message1);
        $this->getConversation()->addMessage($message2);

        // 验证状态
        $this->assertEquals('完整对话测试', $this->getConversation()->getTitle());
        $this->assertEquals('测试完整对话流程', $this->getConversation()->getDescription());
        $this->assertEquals('gpt-4', $this->getConversation()->getModel());
        $this->assertEquals('你是一个测试助手', $this->getConversation()->getSystemPrompt());
        $this->assertTrue($this->getConversation()->isValid());
        $this->assertSame($character, $this->getConversation()->getActor());
        $this->assertCount(2, $this->getConversation()->getMessages());
        $this->assertEquals('完整对话测试', (string) $this->getConversation());
    }

    public function testTitleWithUnicodeCharacters(): void
    {
        $unicodeTitle = '测试 🤖 AI 对话 💬 🚀';
        $this->getConversation()->setTitle($unicodeTitle);

        $this->assertEquals($unicodeTitle, $this->getConversation()->getTitle());
        $this->assertEquals($unicodeTitle, (string) $this->getConversation());
    }

    public function testModelValidation(): void
    {
        $validModels = [
            'gpt-3.5-turbo',
            'gpt-4',
            'gpt-4-turbo',
            'claude-2',
            'claude-3',
        ];

        foreach ($validModels as $model) {
            $this->getConversation()->setModel($model);
            $this->assertEquals($model, $this->getConversation()->getModel());
        }
    }

    public function testTimestampHandling(): void
    {
        $createTime = new \DateTimeImmutable('2023-01-01 10:00:00');
        $updateTime = new \DateTimeImmutable('2023-01-01 11:00:00');

        $this->getConversation()->setCreateTime($createTime);
        $this->getConversation()->setUpdateTime($updateTime);

        $actualCreateTime = $this->getConversation()->getCreateTime();
        $actualUpdateTime = $this->getConversation()->getUpdateTime();

        $this->assertNotNull($actualCreateTime);
        $this->assertNotNull($actualUpdateTime);

        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $actualCreateTime->format('Y-m-d H:i:s'));
        $this->assertEquals($updateTime->format('Y-m-d H:i:s'), $actualUpdateTime->format('Y-m-d H:i:s'));

        $this->assertGreaterThan($this->getConversation()->getCreateTime(), $this->getConversation()->getUpdateTime());
    }
}
