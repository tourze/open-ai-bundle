<?php

namespace OpenAIBundle\Tests\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use PHPUnit\Framework\TestCase;

/**
 * Conversation实体测试
 */
class ConversationTest extends TestCase
{
    private Conversation $conversation;

    protected function setUp(): void
    {
        $this->conversation = new Conversation();
    }

    public function testConstructorInitializesMessagesCollection(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->conversation->getMessages());
        $this->assertCount(0, $this->conversation->getMessages());
    }

    public function testStringRepresentationReturnsTitle(): void
    {
        $title = '测试对话';
        $this->conversation->setTitle($title);
        
        $this->assertEquals($title, (string) $this->conversation);
    }

    public function testTitleGetterAndSetter(): void
    {
        $title = 'AI聊天对话';
        $result = $this->conversation->setTitle($title);
        
        $this->assertSame($this->conversation, $result);
        $this->assertEquals($title, $this->conversation->getTitle());
    }

    public function testTitleSetterWithEmptyString(): void
    {
        $result = $this->conversation->setTitle('');
        
        $this->assertSame($this->conversation, $result);
        $this->assertEquals('', $this->conversation->getTitle());
    }

    public function testTitleSetterWithSpecialCharacters(): void
    {
        $title = '特殊字符测试 #@$% 🤖';
        $this->conversation->setTitle($title);
        
        $this->assertEquals($title, $this->conversation->getTitle());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $description = '这是一个测试对话的描述';
        $result = $this->conversation->setDescription($description);
        
        $this->assertSame($this->conversation, $result);
        $this->assertEquals($description, $this->conversation->getDescription());
    }

    public function testDescriptionSetterWithNull(): void
    {
        $result = $this->conversation->setDescription(null);
        
        $this->assertSame($this->conversation, $result);
        $this->assertNull($this->conversation->getDescription());
    }

    public function testDescriptionSetterWithLongText(): void
    {
        $longText = str_repeat('测试文本', 1000);
        $this->conversation->setDescription($longText);
        
        $this->assertEquals($longText, $this->conversation->getDescription());
    }

    public function testModelGetterAndSetter(): void
    {
        $model = 'gpt-4';
        $result = $this->conversation->setModel($model);
        
        $this->assertSame($this->conversation, $result);
        $this->assertEquals($model, $this->conversation->getModel());
    }

    public function testModelDefaultValue(): void
    {
        $this->assertEquals('gpt-3.5-turbo', $this->conversation->getModel());
    }

    public function testModelSetterWithDifferentModels(): void
    {
        $models = ['gpt-4', 'gpt-3.5-turbo', 'claude-2'];
        
        foreach ($models as $model) {
            $this->conversation->setModel($model);
            $this->assertEquals($model, $this->conversation->getModel());
        }
    }

    public function testSystemPromptGetterAndSetter(): void
    {
        $prompt = '你是一个友好的AI助手';
        $result = $this->conversation->setSystemPrompt($prompt);
        
        $this->assertSame($this->conversation, $result);
        $this->assertEquals($prompt, $this->conversation->getSystemPrompt());
    }

    public function testSystemPromptSetterWithNull(): void
    {
        $result = $this->conversation->setSystemPrompt(null);
        
        $this->assertSame($this->conversation, $result);
        $this->assertNull($this->conversation->getSystemPrompt());
    }

    public function testSystemPromptSetterWithLongPrompt(): void
    {
        $longPrompt = str_repeat('系统提示词测试', 500);
        $this->conversation->setSystemPrompt($longPrompt);
        
        $this->assertEquals($longPrompt, $this->conversation->getSystemPrompt());
    }

    public function testAddMessage(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('setConversation')
            ->with($this->conversation);
        
        $result = $this->conversation->addMessage($message);
        
        $this->assertSame($this->conversation, $result);
        $this->assertTrue($this->conversation->getMessages()->contains($message));
        $this->assertCount(1, $this->conversation->getMessages());
    }

    public function testAddMessageDoesNotAddDuplicate(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('setConversation')
            ->with($this->conversation);
        
        $this->conversation->addMessage($message);
        $this->conversation->addMessage($message); // 添加相同消息
        
        $this->assertCount(1, $this->conversation->getMessages());
    }

    public function testRemoveMessage(): void
    {
        $message = $this->createMock(Message::class);
        $message->method('getConversation')->willReturn($this->conversation);
        $message->expects($this->once())
            ->method('setConversation')
            ->with(null);
        
        $this->conversation->getMessages()->add($message);
        
        $result = $this->conversation->removeMessage($message);
        
        $this->assertSame($this->conversation, $result);
        $this->assertFalse($this->conversation->getMessages()->contains($message));
    }

    public function testRemoveMessageWhenNotInCollection(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->never())
            ->method('setConversation');
        
        $result = $this->conversation->removeMessage($message);
        
        $this->assertSame($this->conversation, $result);
    }

    public function testRemoveMessageWhenConversationNotSame(): void
    {
        $message = $this->createMock(Message::class);
        $otherConversation = new Conversation();
        $message->method('getConversation')->willReturn($otherConversation);
        $message->expects($this->never())
            ->method('setConversation');
        
        $this->conversation->getMessages()->add($message);
        $this->conversation->removeMessage($message);
        
        $this->assertFalse($this->conversation->getMessages()->contains($message));
    }

    public function testClearMessages(): void
    {
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);
        
        $message1->method('getConversation')->willReturn($this->conversation);
        $message2->method('getConversation')->willReturn($this->conversation);
        
        $message1->expects($this->once())->method('setConversation')->with(null);
        $message2->expects($this->once())->method('setConversation')->with(null);
        
        $this->conversation->getMessages()->add($message1);
        $this->conversation->getMessages()->add($message2);
        
        $result = $this->conversation->clearMessages();
        
        $this->assertSame($this->conversation, $result);
        $this->assertCount(0, $this->conversation->getMessages());
    }

    public function testActorGetterAndSetter(): void
    {
        $character = $this->createMock(Character::class);
        $result = $this->conversation->setActor($character);
        
        $this->assertSame($this->conversation, $result);
        $this->assertSame($character, $this->conversation->getActor());
    }

    public function testActorSetterWithNull(): void
    {
        $result = $this->conversation->setActor(null);
        
        $this->assertSame($this->conversation, $result);
        $this->assertNull($this->conversation->getActor());
    }

    public function testValidGetterAndSetter(): void
    {
        $result = $this->conversation->setValid(true);
        
        $this->assertSame($this->conversation, $result);
        $this->assertTrue($this->conversation->isValid());
    }

    public function testValidDefaultValue(): void
    {
        $this->assertFalse($this->conversation->isValid());
    }

    public function testValidSetterWithNull(): void
    {
        $result = $this->conversation->setValid(null);
        
        $this->assertSame($this->conversation, $result);
        $this->assertNull($this->conversation->isValid());
    }

    public function testCreatedByGetterAndSetter(): void
    {
        $createdBy = 'user123';
        $result = $this->conversation->setCreatedBy($createdBy);
        
        $this->assertSame($this->conversation, $result);
        $this->assertEquals($createdBy, $this->conversation->getCreatedBy());
    }

    public function testCreatedBySetterWithNull(): void
    {
        $result = $this->conversation->setCreatedBy(null);
        
        $this->assertSame($this->conversation, $result);
        $this->assertNull($this->conversation->getCreatedBy());
    }

    public function testUpdatedByGetterAndSetter(): void
    {
        $updatedBy = 'admin456';
        $result = $this->conversation->setUpdatedBy($updatedBy);
        
        $this->assertSame($this->conversation, $result);
        $this->assertEquals($updatedBy, $this->conversation->getUpdatedBy());
    }

    public function testUpdatedBySetterWithNull(): void
    {
        $result = $this->conversation->setUpdatedBy(null);
        
        $this->assertSame($this->conversation, $result);
        $this->assertNull($this->conversation->getUpdatedBy());
    }

    public function testCreateTimeGetterAndSetter(): void
    {
        $dateTime = new DateTime('2023-01-01 12:00:00');
        $this->conversation->setCreateTime($dateTime);
        
        $this->assertSame($dateTime, $this->conversation->getCreateTime());
    }

    public function testCreateTimeSetterWithNull(): void
    {
        $this->conversation->setCreateTime(null);
        
        $this->assertNull($this->conversation->getCreateTime());
    }

    public function testUpdateTimeGetterAndSetter(): void
    {
        $dateTime = new DateTime('2023-01-02 15:30:00');
        $this->conversation->setUpdateTime($dateTime);
        
        $this->assertSame($dateTime, $this->conversation->getUpdateTime());
    }

    public function testUpdateTimeSetterWithNull(): void
    {
        $this->conversation->setUpdateTime(null);
        
        $this->assertNull($this->conversation->getUpdateTime());
    }

    public function testGetIdReturnsNull(): void
    {
        $this->assertNull($this->conversation->getId());
    }

    public function testCompleteConversationWorkflow(): void
    {
        // 设置基本属性
        $this->conversation->setTitle('完整对话测试');
        $this->conversation->setDescription('测试完整对话流程');
        $this->conversation->setModel('gpt-4');
        $this->conversation->setSystemPrompt('你是一个测试助手');
        $this->conversation->setValid(true);
        
        // 设置角色
        $character = $this->createMock(Character::class);
        $this->conversation->setActor($character);
        
        // 添加消息
        $message1 = $this->createMock(Message::class);
        $message1->method('getConversation')->willReturn($this->conversation);
        $message1->expects($this->once())->method('setConversation')->with($this->conversation);
        
        $message2 = $this->createMock(Message::class);
        $message2->method('getConversation')->willReturn($this->conversation);
        $message2->expects($this->once())->method('setConversation')->with($this->conversation);
        
        $this->conversation->addMessage($message1);
        $this->conversation->addMessage($message2);
        
        // 验证状态
        $this->assertEquals('完整对话测试', $this->conversation->getTitle());
        $this->assertEquals('测试完整对话流程', $this->conversation->getDescription());
        $this->assertEquals('gpt-4', $this->conversation->getModel());
        $this->assertEquals('你是一个测试助手', $this->conversation->getSystemPrompt());
        $this->assertTrue($this->conversation->isValid());
        $this->assertSame($character, $this->conversation->getActor());
        $this->assertCount(2, $this->conversation->getMessages());
        $this->assertEquals('完整对话测试', (string) $this->conversation);
    }

    public function testTitleWithUnicodeCharacters(): void
    {
        $unicodeTitle = '测试 🤖 AI 对话 💬 🚀';
        $this->conversation->setTitle($unicodeTitle);
        
        $this->assertEquals($unicodeTitle, $this->conversation->getTitle());
        $this->assertEquals($unicodeTitle, (string) $this->conversation);
    }

    public function testModelValidation(): void
    {
        $validModels = [
            'gpt-3.5-turbo',
            'gpt-4',
            'gpt-4-turbo',
            'claude-2',
            'claude-3'
        ];
        
        foreach ($validModels as $model) {
            $this->conversation->setModel($model);
            $this->assertEquals($model, $this->conversation->getModel());
        }
    }

    public function testTimestampHandling(): void
    {
        $createTime = new DateTime('2023-01-01 10:00:00');
        $updateTime = new DateTime('2023-01-01 11:00:00');
        
        $this->conversation->setCreateTime($createTime);
        $this->conversation->setUpdateTime($updateTime);
        
        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $this->conversation->getCreateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals($updateTime->format('Y-m-d H:i:s'), $this->conversation->getUpdateTime()->format('Y-m-d H:i:s'));
        
        $this->assertTrue($this->conversation->getUpdateTime() > $this->conversation->getCreateTime());
    }
} 