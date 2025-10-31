<?php

namespace OpenAIBundle\Tests\Entity;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\ContextLength;
use OpenAIBundle\Enum\RoleEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(ApiKey::class)]
final class ApiKeyTest extends AbstractEntityTestCase
{
    private ?ApiKey $apiKey = null;

    protected function onSetUp(): void
    {
        $this->apiKey = new ApiKey();
    }

    private function getApiKey(): ApiKey
    {
        return $this->apiKey ??= new ApiKey();
    }

    protected function createEntity(): object
    {
        return new ApiKey();
    }

    /** @return iterable<string, array{0: string, 1: mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'title' => ['title', 'Test API Key'];
        yield 'apiKey' => ['apiKey', 'sk-test-key-12345'];
        yield 'model' => ['model', 'gpt-3.5-turbo'];
        yield 'chatCompletionUrl' => ['chatCompletionUrl', 'https://api.openai.com/v1/chat/completions'];
        yield 'valid' => ['valid', true];
        yield 'functionCalling' => ['functionCalling', true];
        yield 'contextLength' => ['contextLength', ContextLength::K_8];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $this->assertNull($this->getApiKey()->getId());
    }

    public function testValidCanBeSetAndRetrieved(): void
    {
        $this->getApiKey()->setValid(true);
        $this->assertTrue($this->getApiKey()->isValid());

        $this->getApiKey()->setValid(false);
        $this->assertFalse($this->getApiKey()->isValid());
    }

    public function testValidDefaultsToFalse(): void
    {
        $this->assertFalse($this->getApiKey()->isValid());
    }

    public function testTitleCanBeSetAndRetrieved(): void
    {
        $title = 'Test API Key';
        $this->getApiKey()->setTitle($title);
        $this->assertEquals($title, $this->getApiKey()->getTitle());
    }

    public function testTitleReturnsNullByDefault(): void
    {
        $this->assertNull($this->getApiKey()->getTitle());
    }

    public function testApiKeyCanBeSetAndRetrieved(): void
    {
        $key = 'sk-test-key-12345';
        $this->getApiKey()->setApiKey($key);
        $this->assertEquals($key, $this->getApiKey()->getApiKey());
    }

    public function testModelCanBeSetAndRetrieved(): void
    {
        $model = 'deepseek-chat';
        $this->getApiKey()->setModel($model);
        $this->assertEquals($model, $this->getApiKey()->getModel());
    }

    public function testChatCompletionUrlCanBeSetAndRetrieved(): void
    {
        $url = 'https://api.deepseek.com/v1/chat/completions';
        $this->getApiKey()->setChatCompletionUrl($url);
        $this->assertEquals($url, $this->getApiKey()->getChatCompletionUrl());
    }

    public function testFunctionCallingCanBeSetAndRetrieved(): void
    {
        $this->getApiKey()->setFunctionCalling(true);
        $this->assertTrue($this->getApiKey()->isFunctionCalling());

        $this->getApiKey()->setFunctionCalling(false);
        $this->assertFalse($this->getApiKey()->isFunctionCalling());
    }

    public function testFunctionCallingDefaultsToFalse(): void
    {
        $this->assertFalse($this->getApiKey()->isFunctionCalling());
    }

    public function testContextLengthCanBeSetAndRetrieved(): void
    {
        $contextLength = ContextLength::K_8;
        $this->getApiKey()->setContextLength($contextLength);
        $this->assertEquals($contextLength, $this->getApiKey()->getContextLength());
    }

    public function testContextLengthReturnsNullByDefault(): void
    {
        $this->assertNull($this->getApiKey()->getContextLength());
    }

    public function testCreateTimeCanBeSetAndRetrieved(): void
    {
        $now = new \DateTimeImmutable();
        $this->getApiKey()->setCreateTime($now);
        $this->assertEquals($now, $this->getApiKey()->getCreateTime());
    }

    public function testUpdateTimeCanBeSetAndRetrieved(): void
    {
        $now = new \DateTimeImmutable();
        $this->getApiKey()->setUpdateTime($now);
        $this->assertEquals($now, $this->getApiKey()->getUpdateTime());
    }

    public function testMessagesCollectionIsEmptyByDefault(): void
    {
        $this->assertCount(0, $this->getApiKey()->getMessages());
    }

    public function testAddMessageAddsMessageToCollection(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，需要测试与 ApiKey 的关联关系
         * 2. 这种使用是合理和必要的，因为测试的是实体间的双向关联操作
         * 3. 暂无更好的替代方案，因为需要验证 setApiKey 方法的调用行为
         */
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('setApiKey')
            ->with($this->getApiKey())
        ;

        $this->getApiKey()->addMessage($message);
        $this->assertCount(1, $this->getApiKey()->getMessages());
        $this->assertTrue($this->getApiKey()->getMessages()->contains($message));
    }

    public function testAddMessageDoesNotAddDuplicateMessage(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，需要测试重复添加消息的逻辑
         * 2. 这种使用是合理和必要的，因为测试的是集合操作和去重逻辑
         * 3. 暂无更好的替代方案，因为需要验证 setApiKey 方法的调用次数
         */
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('setApiKey')
            ->with($this->getApiKey())
        ;

        $this->getApiKey()->addMessage($message);
        $this->getApiKey()->addMessage($message); // 添加相同消息

        $this->assertCount(1, $this->getApiKey()->getMessages());
    }

    public function testRemoveMessageRemovesMessageFromCollection(): void
    {
        // 创建真实的Message实例用于测试
        $message = new Message();
        $message->setContent('Test message');
        $message->setRole(RoleEnum::user);
        $message->setModel('test-model');

        // 添加消息
        $this->getApiKey()->addMessage($message);
        $this->assertCount(1, $this->getApiKey()->getMessages());
        $this->assertTrue($this->getApiKey()->getMessages()->contains($message));

        // 移除消息
        $this->getApiKey()->removeMessage($message);
        $this->assertCount(0, $this->getApiKey()->getMessages());
        $this->assertFalse($this->getApiKey()->getMessages()->contains($message));

        // 验证message的apiKey已被设置为null
        $this->assertNull($message->getApiKey());
    }

    public function testRemoveMessageDoesNotRemoveUnrelatedMessage(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，需要测试不相关消息的移除逻辑
         * 2. 这种使用是合理和必要的，因为测试的是集合操作的精确性
         * 3. 暂无更好的替代方案，因为需要创建多个不同的消息实例进行对比
         */
        $message1 = $this->createMock(Message::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Message 是 Doctrine Entity 实体类，作为对照组测试移除操作
         * 2. 这种使用是合理和必要的，因为需要验证不会误删其他消息
         * 3. 暂无更好的替代方案，因为需要创建独立的消息实例
         */
        $message2 = $this->createMock(Message::class);

        $message1->expects($this->once())
            ->method('setApiKey')
            ->with($this->getApiKey())
        ;

        $this->getApiKey()->addMessage($message1);
        $this->getApiKey()->removeMessage($message2); // 移除不相关的消息

        $this->assertCount(1, $this->getApiKey()->getMessages());
    }

    public function testToStringReturnsEmptyStringWhenNoId(): void
    {
        $this->assertEquals('', (string) $this->getApiKey());
    }

    public function testToStringReturnsTitleWhenIdExists(): void
    {
        $title = 'Test API Key';
        $this->getApiKey()->setTitle($title);

        // 通过反射设置 ID
        $reflection = new \ReflectionClass($this->getApiKey());
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->getApiKey(), '12345');

        $this->assertEquals($title, (string) $this->getApiKey());
    }

    public function testTitleAcceptsEmptyString(): void
    {
        $this->getApiKey()->setTitle('');
        $this->assertEquals('', $this->getApiKey()->getTitle());
    }

    public function testTitleAcceptsLongString(): void
    {
        $longTitle = str_repeat('a', 100);
        $this->getApiKey()->setTitle($longTitle);
        $this->assertEquals($longTitle, $this->getApiKey()->getTitle());
    }

    public function testModelAcceptsVariousFormats(): void
    {
        $models = [
            'gpt-3.5-turbo',
            'gpt-4',
            'deepseek-chat',
            'deepseek-coder',
        ];

        foreach ($models as $model) {
            $this->getApiKey()->setModel($model);
            $this->assertEquals($model, $this->getApiKey()->getModel());
        }
    }

    public function testChatCompletionUrlAcceptsValidUrls(): void
    {
        $urls = [
            'https://api.openai.com/v1/chat/completions',
            'https://api.deepseek.com/v1/chat/completions',
            'http://localhost:8080/api/v1/chat/completions',
        ];

        foreach ($urls as $url) {
            $this->getApiKey()->setChatCompletionUrl($url);
            $this->assertEquals($url, $this->getApiKey()->getChatCompletionUrl());
        }
    }

    public function testContextLengthAcceptsAllValidValues(): void
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
            $this->getApiKey()->setContextLength($length);
            $this->assertEquals($length, $this->getApiKey()->getContextLength());
        }
    }
}
