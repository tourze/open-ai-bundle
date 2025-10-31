<?php

namespace OpenAIBundle\Tests\Entity;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * Message实体测试
 *
 * @internal
 */
#[CoversClass(Message::class)]
final class MessageTest extends AbstractEntityTestCase
{
    private ?Message $message = null;

    private function getMessage(): Message
    {
        if (null === $this->message) {
            $this->message = new Message();
        }

        return $this->message;
    }

    protected function createEntity(): object
    {
        return new Message();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'msgId' => ['msgId', 'msg_test_12345'];
        yield 'role' => ['role', RoleEnum::assistant];
        yield 'content' => ['content', 'Test message content'];
        yield 'reasoningContent' => ['reasoningContent', 'Reasoning content'];
        yield 'model' => ['model', 'gpt-4'];
        yield 'toolCalls' => ['toolCalls', [['id' => 'call_123', 'function' => ['name' => 'test_function']]]];
        yield 'toolCallId' => ['toolCallId', 'call_456'];
        yield 'promptTokens' => ['promptTokens', 100];
        yield 'completionTokens' => ['completionTokens', 50];
        yield 'totalTokens' => ['totalTokens', 150];
        yield 'createdBy' => ['createdBy', 'user123'];
        yield 'updatedBy' => ['updatedBy', 'admin456'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testStringRepresentationReturnsContent(): void
    {
        $content = '这是测试消息内容';
        $this->getMessage()->setContent($content);

        $this->assertEquals($content, (string) $this->getMessage());
    }

    public function testGetIdReturnsNull(): void
    {
        $this->assertNull($this->getMessage()->getId());
    }

    public function testMsgIdGetterAndSetter(): void
    {
        $msgId = 'msg_test_12345';
        $this->getMessage()->setMsgId($msgId);

        $this->assertEquals($msgId, $this->getMessage()->getMsgId());
    }

    public function testMsgIdWithDifferentFormats(): void
    {
        $msgIds = [
            'msg_12345',
            'message-abc-def',
            'test_msg_001',
            'uuid-like-message-id',
        ];

        foreach ($msgIds as $msgId) {
            $this->getMessage()->setMsgId($msgId);
            $this->assertEquals($msgId, $this->getMessage()->getMsgId());
        }
    }

    public function testConversationGetterAndSetter(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，作为 Message 的所属对话
         * 2. 这种使用是合理和必要的，因为测试的是实体间的关联关系
         * 3. 暂无更好的替代方案，因为需要验证对象引用的一致性
         */
        $conversation = $this->createMock(Conversation::class);
        $this->getMessage()->setConversation($conversation);

        $this->assertSame($conversation, $this->getMessage()->getConversation());
    }

    public function testConversationSetterWithNull(): void
    {
        $this->getMessage()->setConversation(null);

        $this->assertNull($this->getMessage()->getConversation());
    }

    public function testRoleGetterAndSetter(): void
    {
        $role = RoleEnum::assistant;
        $this->getMessage()->setRole($role);

        $this->assertEquals($role, $this->getMessage()->getRole());
    }

    public function testRoleDefaultValue(): void
    {
        $this->assertEquals(RoleEnum::user, $this->getMessage()->getRole());
    }

    public function testRoleSetterWithAllValidRoles(): void
    {
        $roles = [
            RoleEnum::user,
            RoleEnum::assistant,
            RoleEnum::system,
            RoleEnum::tool,
        ];

        foreach ($roles as $role) {
            $this->getMessage()->setRole($role);
            $this->assertEquals($role, $this->getMessage()->getRole());
        }
    }

    public function testContentGetterAndSetter(): void
    {
        $content = '用户提问内容';
        $this->getMessage()->setContent($content);

        $this->assertEquals($content, $this->getMessage()->getContent());
    }

    public function testContentSetterWithEmptyString(): void
    {
        $this->getMessage()->setContent('');

        $this->assertEquals('', $this->getMessage()->getContent());
    }

    public function testContentSetterWithLongText(): void
    {
        $longContent = str_repeat('这是很长的文本内容。', 1000);
        $this->getMessage()->setContent($longContent);

        $this->assertEquals($longContent, $this->getMessage()->getContent());
    }

    public function testContentSetterWithSpecialCharacters(): void
    {
        $content = '特殊字符测试: #@$%^&*()_+ 🚀 💻 ✅';
        $this->getMessage()->setContent($content);

        $this->assertEquals($content, $this->getMessage()->getContent());
    }

    public function testAppendContent(): void
    {
        $this->getMessage()->setContent('初始内容');
        $this->getMessage()->appendContent('追加内容');

        $this->assertEquals('初始内容追加内容', $this->getMessage()->getContent());
    }

    public function testAppendContentMultipleTimes(): void
    {
        $this->getMessage()->setContent('开始');
        $this->getMessage()->appendContent(' - 中间');
        $this->getMessage()->appendContent(' - 结尾');

        $this->assertEquals('开始 - 中间 - 结尾', $this->getMessage()->getContent());
    }

    public function testAppendContentToEmptyContent(): void
    {
        $this->getMessage()->setContent('');
        $this->getMessage()->appendContent('新内容');

        $this->assertEquals('新内容', $this->getMessage()->getContent());
    }

    public function testReasoningContentGetterAndSetter(): void
    {
        $reasoning = '思考过程：这是推理内容';
        $this->getMessage()->setReasoningContent($reasoning);

        $this->assertEquals($reasoning, $this->getMessage()->getReasoningContent());
    }

    public function testReasoningContentSetterWithNull(): void
    {
        $this->getMessage()->setReasoningContent(null);

        $this->assertNull($this->getMessage()->getReasoningContent());
    }

    public function testAppendReasoningContent(): void
    {
        $this->getMessage()->setReasoningContent('初始推理');
        $this->getMessage()->appendReasoningContent('，继续推理');

        $this->assertEquals('初始推理，继续推理', $this->getMessage()->getReasoningContent());
    }

    public function testAppendReasoningContentToNullContent(): void
    {
        $this->getMessage()->setReasoningContent(null);
        $this->getMessage()->appendReasoningContent('新推理内容');

        $this->assertEquals('新推理内容', $this->getMessage()->getReasoningContent());
    }

    public function testModelGetterAndSetter(): void
    {
        $model = 'gpt-4';
        $this->getMessage()->setModel($model);

        $this->assertEquals($model, $this->getMessage()->getModel());
    }

    public function testModelSetterWithDifferentModels(): void
    {
        $models = [
            'gpt-3.5-turbo',
            'gpt-4',
            'deepseek-chat',
            'claude-3',
            'custom-model',
        ];

        foreach ($models as $model) {
            $this->getMessage()->setModel($model);
            $this->assertEquals($model, $this->getMessage()->getModel());
        }
    }

    public function testToolCallsGetterAndSetter(): void
    {
        $toolCalls = [
            [
                'id' => 'call_123',
                'function' => [
                    'name' => 'test_function',
                    'arguments' => '{"param": "value"}',
                ],
            ],
        ];

        $this->getMessage()->setToolCalls($toolCalls);

        $this->assertEquals($toolCalls, $this->getMessage()->getToolCalls());
    }

    public function testToolCallsSetterWithNull(): void
    {
        $this->getMessage()->setToolCalls(null);

        $this->assertNull($this->getMessage()->getToolCalls());
    }

    public function testAddToolCall(): void
    {
        $toolCall = [
            'id' => 'call_456',
            'function' => [
                'name' => 'another_function',
                'arguments' => '{"x": 1, "y": 2}',
            ],
        ];

        $this->getMessage()->addToolCall($toolCall);

        $toolCalls = $this->getMessage()->getToolCalls();
        $this->assertIsArray($toolCalls);
        $this->assertCount(1, $toolCalls);
        $this->assertEquals($toolCall, $toolCalls[0]);
    }

    public function testAddToolCallMultipleTimes(): void
    {
        $toolCall1 = ['id' => 'call_1', 'function' => ['name' => 'func1']];
        $toolCall2 = ['id' => 'call_2', 'function' => ['name' => 'func2']];

        $this->getMessage()->addToolCall($toolCall1);
        $this->getMessage()->addToolCall($toolCall2);

        $toolCalls = $this->getMessage()->getToolCalls();
        $this->assertIsArray($toolCalls);
        $this->assertCount(2, $toolCalls);
        $this->assertEquals($toolCall1, $toolCalls[0]);
        $this->assertEquals($toolCall2, $toolCalls[1]);
    }

    public function testAddToolCallToExistingArray(): void
    {
        $existing = [['id' => 'existing', 'function' => ['name' => 'existing_func']]];
        $this->getMessage()->setToolCalls($existing);

        $newCall = ['id' => 'new', 'function' => ['name' => 'new_func']];
        $this->getMessage()->addToolCall($newCall);

        $toolCalls = $this->getMessage()->getToolCalls();
        $this->assertIsArray($toolCalls);
        $this->assertCount(2, $toolCalls);
        $this->assertEquals($existing[0], $toolCalls[0]);
        $this->assertEquals($newCall, $toolCalls[1]);
    }

    public function testToolCallIdGetterAndSetter(): void
    {
        $toolCallId = 'call_test_789';
        $this->getMessage()->setToolCallId($toolCallId);

        $this->assertEquals($toolCallId, $this->getMessage()->getToolCallId());
    }

    public function testToolCallIdSetterWithNull(): void
    {
        $this->getMessage()->setToolCallId(null);

        $this->assertNull($this->getMessage()->getToolCallId());
    }

    public function testPromptTokensGetterAndSetter(): void
    {
        $tokens = 150;
        $this->getMessage()->setPromptTokens($tokens);

        $this->assertEquals($tokens, $this->getMessage()->getPromptTokens());
    }

    public function testPromptTokensDefaultValue(): void
    {
        $this->assertEquals(0, $this->getMessage()->getPromptTokens());
    }

    public function testPromptTokensWithBoundaryValues(): void
    {
        $values = [0, 1, 100, 1000, 4096, 8192];

        foreach ($values as $value) {
            $this->getMessage()->setPromptTokens($value);
            $this->assertEquals($value, $this->getMessage()->getPromptTokens());
        }
    }

    public function testCompletionTokensGetterAndSetter(): void
    {
        $tokens = 75;
        $this->getMessage()->setCompletionTokens($tokens);

        $this->assertEquals($tokens, $this->getMessage()->getCompletionTokens());
    }

    public function testCompletionTokensDefaultValue(): void
    {
        $this->assertEquals(0, $this->getMessage()->getCompletionTokens());
    }

    public function testCompletionTokensWithBoundaryValues(): void
    {
        $values = [0, 1, 50, 500, 2048, 4096];

        foreach ($values as $value) {
            $this->getMessage()->setCompletionTokens($value);
            $this->assertEquals($value, $this->getMessage()->getCompletionTokens());
        }
    }

    public function testTotalTokensGetterAndSetter(): void
    {
        $tokens = 225;
        $this->getMessage()->setTotalTokens($tokens);

        $this->assertEquals($tokens, $this->getMessage()->getTotalTokens());
    }

    public function testTotalTokensDefaultValue(): void
    {
        $this->assertEquals(0, $this->getMessage()->getTotalTokens());
    }

    public function testApiKeyGetterAndSetter(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 Doctrine Entity 实体类，作为 Message 使用的 API 键
         * 2. 这种使用是合理和必要的，因为测试的是实体间的关联关系
         * 3. 暂无更好的替代方案，因为需要验证对象引用的一致性
         */
        $apiKey = $this->createMock(ApiKey::class);
        $this->getMessage()->setApiKey($apiKey);

        $this->assertSame($apiKey, $this->getMessage()->getApiKey());
    }

    public function testApiKeySetterWithNull(): void
    {
        $this->getMessage()->setApiKey(null);

        $this->assertNull($this->getMessage()->getApiKey());
    }

    public function testCreatedByGetterAndSetter(): void
    {
        $createdBy = 'user123';
        $this->getMessage()->setCreatedBy($createdBy);

        $this->assertEquals($createdBy, $this->getMessage()->getCreatedBy());
    }

    public function testCreatedBySetterWithNull(): void
    {
        $this->getMessage()->setCreatedBy(null);

        $this->assertNull($this->getMessage()->getCreatedBy());
    }

    public function testUpdatedByGetterAndSetter(): void
    {
        $updatedBy = 'admin456';
        $this->getMessage()->setUpdatedBy($updatedBy);

        $this->assertEquals($updatedBy, $this->getMessage()->getUpdatedBy());
    }

    public function testUpdatedBySetterWithNull(): void
    {
        $this->getMessage()->setUpdatedBy(null);

        $this->assertNull($this->getMessage()->getUpdatedBy());
    }

    public function testCreateTimeGetterAndSetter(): void
    {
        $dateTime = new \DateTimeImmutable('2023-01-01 12:00:00');
        $this->getMessage()->setCreateTime($dateTime);

        $this->assertSame($dateTime, $this->getMessage()->getCreateTime());
    }

    public function testCreateTimeSetterWithNull(): void
    {
        $this->getMessage()->setCreateTime(null);

        $this->assertNull($this->getMessage()->getCreateTime());
    }

    public function testUpdateTimeGetterAndSetter(): void
    {
        $dateTime = new \DateTimeImmutable('2023-01-02 15:30:00');
        $this->getMessage()->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $this->getMessage()->getUpdateTime());
    }

    public function testUpdateTimeSetterWithNull(): void
    {
        $this->getMessage()->setUpdateTime(null);

        $this->assertNull($this->getMessage()->getUpdateTime());
    }

    public function testToArrayBasicMessage(): void
    {
        $this->getMessage()->setRole(RoleEnum::user);
        $this->getMessage()->setContent('Hello world');

        $array = $this->getMessage()->toArray();

        $expected = [
            'role' => 'user',
            'content' => 'Hello world',
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithToolCalls(): void
    {
        $this->getMessage()->setRole(RoleEnum::assistant);
        $this->getMessage()->setContent('I will help you');

        $toolCalls = [
            [
                'id' => 'call_123',
                'function' => [
                    'name' => 'get_weather',
                    'arguments' => '{"location": "Beijing"}',
                ],
            ],
        ];
        $this->getMessage()->setToolCalls($toolCalls);

        $array = $this->getMessage()->toArray();

        $expected = [
            'role' => 'assistant',
            'content' => 'I will help you',
            'tool_calls' => $toolCalls,
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithToolCallId(): void
    {
        $this->getMessage()->setRole(RoleEnum::tool);
        $this->getMessage()->setContent('{"result": "success"}');
        $this->getMessage()->setToolCallId('call_456');

        $array = $this->getMessage()->toArray();

        $expected = [
            'role' => 'tool',
            'content' => '{"result": "success"}',
            'tool_call_id' => 'call_456',
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithBothToolCallsAndToolCallId(): void
    {
        $this->getMessage()->setRole(RoleEnum::assistant);
        $this->getMessage()->setContent('Complete response');

        $toolCalls = [['id' => 'call_1', 'function' => ['name' => 'func1']]];
        $this->getMessage()->setToolCalls($toolCalls);
        $this->getMessage()->setToolCallId('call_response');

        $array = $this->getMessage()->toArray();

        $expected = [
            'role' => 'assistant',
            'content' => 'Complete response',
            'tool_calls' => $toolCalls,
            'tool_call_id' => 'call_response',
        ];

        $this->assertEquals($expected, $array);
    }

    public function testCompleteMessageWorkflow(): void
    {
        // 创建完整的消息
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，作为完整测试流程的一部分
         * 2. 这种使用是合理和必要的，因为测试的是完整的业务流程
         * 3. 暂无更好的替代方案，因为需要与其他组件配合测试
         */
        $conversation = $this->createMock(Conversation::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 Doctrine Entity 实体类，作为完整测试流程的一部分
         * 2. 这种使用是合理和必要的，因为测试的是完整的业务流程
         * 3. 暂无更好的替代方案，因为需要与其他组件配合测试
         */
        $apiKey = $this->createMock(ApiKey::class);

        $this->getMessage()->setMsgId('msg_complete_test');
        $this->getMessage()->setConversation($conversation);
        $this->getMessage()->setRole(RoleEnum::assistant);
        $this->getMessage()->setContent('完整的AI回复');
        $this->getMessage()->setReasoningContent('思考过程');
        $this->getMessage()->setModel('gpt-4');
        $this->getMessage()->setPromptTokens(100);
        $this->getMessage()->setCompletionTokens(50);
        $this->getMessage()->setTotalTokens(150);
        $this->getMessage()->setApiKey($apiKey);
        $this->getMessage()->setCreatedBy('user123');

        $createTime = new \DateTimeImmutable();
        $this->getMessage()->setCreateTime($createTime);

        // 验证所有属性
        $this->assertEquals('msg_complete_test', $this->getMessage()->getMsgId());
        $this->assertSame($conversation, $this->getMessage()->getConversation());
        $this->assertEquals(RoleEnum::assistant, $this->getMessage()->getRole());
        $this->assertEquals('完整的AI回复', $this->getMessage()->getContent());
        $this->assertEquals('思考过程', $this->getMessage()->getReasoningContent());
        $this->assertEquals('gpt-4', $this->getMessage()->getModel());
        $this->assertEquals(100, $this->getMessage()->getPromptTokens());
        $this->assertEquals(50, $this->getMessage()->getCompletionTokens());
        $this->assertEquals(150, $this->getMessage()->getTotalTokens());
        $this->assertSame($apiKey, $this->getMessage()->getApiKey());
        $this->assertEquals('user123', $this->getMessage()->getCreatedBy());
        $this->assertSame($createTime, $this->getMessage()->getCreateTime());
        $this->assertEquals('完整的AI回复', (string) $this->getMessage());
    }

    public function testTokenCalculationScenario(): void
    {
        // 测试令牌计算场景
        $this->getMessage()->setPromptTokens(200);
        $this->getMessage()->setCompletionTokens(100);
        $this->getMessage()->setTotalTokens(300);

        // 验证令牌总数应该等于输入+输出
        $this->assertEquals(
            $this->getMessage()->getPromptTokens() + $this->getMessage()->getCompletionTokens(),
            $this->getMessage()->getTotalTokens()
        );
    }

    public function testUnicodeContentHandling(): void
    {
        $unicodeContent = '测试Unicode内容 🚀 💻 ✅ 中英混合 English mixed';
        $this->getMessage()->setContent($unicodeContent);

        $this->assertEquals($unicodeContent, $this->getMessage()->getContent());
        $this->assertEquals($unicodeContent, (string) $this->getMessage());
    }

    public function testJsonContentInToolCall(): void
    {
        $jsonContent = '{"status": "success", "data": {"items": [1, 2, 3], "count": 3}}';
        $this->getMessage()->setContent($jsonContent);
        $this->getMessage()->setRole(RoleEnum::tool);

        $array = $this->getMessage()->toArray();

        $this->assertEquals('tool', $array['role']);
        $this->assertEquals($jsonContent, $array['content']);
    }
}
