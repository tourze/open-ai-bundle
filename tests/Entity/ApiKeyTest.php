<?php

namespace OpenAIBundle\Tests\Entity;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\ContextLength;
use OpenAIBundle\Enum\RoleEnum;
use PHPUnit\Framework\TestCase;

class ApiKeyTest extends TestCase
{
    private ApiKey $apiKey;

    protected function setUp(): void
    {
        $this->apiKey = new ApiKey();
    }

    public function test_getId_returnsNullByDefault(): void
    {
        $this->assertNull($this->apiKey->getId());
    }

    public function test_valid_canBeSetAndRetrieved(): void
    {
        $this->apiKey->setValid(true);
        $this->assertTrue($this->apiKey->isValid());

        $this->apiKey->setValid(false);
        $this->assertFalse($this->apiKey->isValid());
    }

    public function test_valid_defaultsToFalse(): void
    {
        $this->assertFalse($this->apiKey->isValid());
    }

    public function test_title_canBeSetAndRetrieved(): void
    {
        $title = 'Test API Key';
        $this->apiKey->setTitle($title);
        $this->assertEquals($title, $this->apiKey->getTitle());
    }

    public function test_title_returnsNullByDefault(): void
    {
        $this->assertNull($this->apiKey->getTitle());
    }

    public function test_apiKey_canBeSetAndRetrieved(): void
    {
        $key = 'sk-test-key-12345';
        $this->apiKey->setApiKey($key);
        $this->assertEquals($key, $this->apiKey->getApiKey());
    }

    public function test_model_canBeSetAndRetrieved(): void
    {
        $model = 'deepseek-chat';
        $this->apiKey->setModel($model);
        $this->assertEquals($model, $this->apiKey->getModel());
    }

    public function test_chatCompletionUrl_canBeSetAndRetrieved(): void
    {
        $url = 'https://api.deepseek.com/v1/chat/completions';
        $this->apiKey->setChatCompletionUrl($url);
        $this->assertEquals($url, $this->apiKey->getChatCompletionUrl());
    }

    public function test_functionCalling_canBeSetAndRetrieved(): void
    {
        $this->apiKey->setFunctionCalling(true);
        $this->assertTrue($this->apiKey->isFunctionCalling());

        $this->apiKey->setFunctionCalling(false);
        $this->assertFalse($this->apiKey->isFunctionCalling());
    }

    public function test_functionCalling_defaultsToFalse(): void
    {
        $this->assertFalse($this->apiKey->isFunctionCalling());
    }

    public function test_contextLength_canBeSetAndRetrieved(): void
    {
        $contextLength = ContextLength::K_8;
        $this->apiKey->setContextLength($contextLength);
        $this->assertEquals($contextLength, $this->apiKey->getContextLength());
    }

    public function test_contextLength_returnsNullByDefault(): void
    {
        $this->assertNull($this->apiKey->getContextLength());
    }

    public function test_createTime_canBeSetAndRetrieved(): void
    {
        $now = new \DateTimeImmutable();
        $this->apiKey->setCreateTime($now);
        $this->assertEquals($now, $this->apiKey->getCreateTime());
    }

    public function test_updateTime_canBeSetAndRetrieved(): void
    {
        $now = new \DateTimeImmutable();
        $this->apiKey->setUpdateTime($now);
        $this->assertEquals($now, $this->apiKey->getUpdateTime());
    }

    public function test_messages_collection_isEmptyByDefault(): void
    {
        $this->assertCount(0, $this->apiKey->getMessages());
    }

    public function test_addMessage_addsMessageToCollection(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
                ->method('setApiKey')
                ->with($this->apiKey);

        $this->apiKey->addMessage($message);
        $this->assertCount(1, $this->apiKey->getMessages());
        $this->assertTrue($this->apiKey->getMessages()->contains($message));
    }

    public function test_addMessage_doesNotAddDuplicateMessage(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
                ->method('setApiKey')
                ->with($this->apiKey);

        $this->apiKey->addMessage($message);
        $this->apiKey->addMessage($message); // 添加相同消息

        $this->assertCount(1, $this->apiKey->getMessages());
    }

    public function test_removeMessage_removesMessageFromCollection(): void
    {
        // 创建真实的Message实例用于测试
        $message = new Message();
        $message->setContent('Test message');
        $message->setRole(RoleEnum::user);
        $message->setModel('test-model');
        
        // 添加消息
        $this->apiKey->addMessage($message);
        $this->assertCount(1, $this->apiKey->getMessages());
        $this->assertTrue($this->apiKey->getMessages()->contains($message));
        
        // 移除消息
        $this->apiKey->removeMessage($message);
        $this->assertCount(0, $this->apiKey->getMessages());
        $this->assertFalse($this->apiKey->getMessages()->contains($message));
        
        // 验证message的apiKey已被设置为null
        $this->assertNull($message->getApiKey());
    }

    public function test_removeMessage_doesNotRemoveUnrelatedMessage(): void
    {
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);

        $message1->expects($this->once())
                 ->method('setApiKey')
                 ->with($this->apiKey);

        $this->apiKey->addMessage($message1);
        $this->apiKey->removeMessage($message2); // 移除不相关的消息

        $this->assertCount(1, $this->apiKey->getMessages());
    }

    public function test_toString_returnsEmptyStringWhenNoId(): void
    {
        $this->assertEquals('', (string) $this->apiKey);
    }

    public function test_toString_returnsTitleWhenIdExists(): void
    {
        $title = 'Test API Key';
        $this->apiKey->setTitle($title);

        // 通过反射设置 ID
        $reflection = new \ReflectionClass($this->apiKey);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->apiKey, '12345');

        $this->assertEquals($title, (string) $this->apiKey);
    }

    public function test_title_acceptsEmptyString(): void
    {
        $this->apiKey->setTitle('');
        $this->assertEquals('', $this->apiKey->getTitle());
    }

    public function test_title_acceptsLongString(): void
    {
        $longTitle = str_repeat('a', 100);
        $this->apiKey->setTitle($longTitle);
        $this->assertEquals($longTitle, $this->apiKey->getTitle());
    }

    public function test_model_acceptsVariousFormats(): void
    {
        $models = [
            'gpt-3.5-turbo',
            'gpt-4',
            'deepseek-chat',
            'deepseek-coder',
        ];

        foreach ($models as $model) {
            $this->apiKey->setModel($model);
            $this->assertEquals($model, $this->apiKey->getModel());
        }
    }

    public function test_chatCompletionUrl_acceptsValidUrls(): void
    {
        $urls = [
            'https://api.openai.com/v1/chat/completions',
            'https://api.deepseek.com/v1/chat/completions',
            'http://localhost:8080/api/v1/chat/completions',
        ];

        foreach ($urls as $url) {
            $this->apiKey->setChatCompletionUrl($url);
            $this->assertEquals($url, $this->apiKey->getChatCompletionUrl());
        }
    }

    public function test_contextLength_acceptsAllValidValues(): void
    {
        $contextLengths = [
            ContextLength::K_4,
            ContextLength::K_8,
            ContextLength::K_16,
            ContextLength::K_32,
            ContextLength::K_64,
            ContextLength::K_128,
        ];

        foreach ($contextLengths as $length) {
            $this->apiKey->setContextLength($length);
            $this->assertEquals($length, $this->apiKey->getContextLength());
        }
    }
} 