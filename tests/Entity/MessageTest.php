<?php

namespace OpenAIBundle\Tests\Entity;

use DateTimeImmutable;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use PHPUnit\Framework\TestCase;

/**
 * Message实体测试
 */
class MessageTest extends TestCase
{
    private Message $message;

    protected function setUp(): void
    {
        $this->message = new Message();
    }

    public function testStringRepresentationReturnsContent(): void
    {
        $content = '这是测试消息内容';
        $this->message->setContent($content);
        
        $this->assertEquals($content, (string) $this->message);
    }

    public function testGetIdReturnsNull(): void
    {
        $this->assertNull($this->message->getId());
    }

    public function testMsgIdGetterAndSetter(): void
    {
        $msgId = 'msg_test_12345';
        $result = $this->message->setMsgId($msgId);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($msgId, $this->message->getMsgId());
    }

    public function testMsgIdWithDifferentFormats(): void
    {
        $msgIds = [
            'msg_12345',
            'message-abc-def',
            'test_msg_001',
            'uuid-like-message-id'
        ];
        
        foreach ($msgIds as $msgId) {
            $this->message->setMsgId($msgId);
            $this->assertEquals($msgId, $this->message->getMsgId());
        }
    }

    public function testConversationGetterAndSetter(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $result = $this->message->setConversation($conversation);
        
        $this->assertSame($this->message, $result);
        $this->assertSame($conversation, $this->message->getConversation());
    }

    public function testConversationSetterWithNull(): void
    {
        $result = $this->message->setConversation(null);
        
        $this->assertSame($this->message, $result);
        $this->assertNull($this->message->getConversation());
    }

    public function testRoleGetterAndSetter(): void
    {
        $role = RoleEnum::assistant;
        $result = $this->message->setRole($role);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($role, $this->message->getRole());
    }

    public function testRoleDefaultValue(): void
    {
        $this->assertEquals(RoleEnum::user, $this->message->getRole());
    }

    public function testRoleSetterWithAllValidRoles(): void
    {
        $roles = [
            RoleEnum::user,
            RoleEnum::assistant,
            RoleEnum::system,
            RoleEnum::tool
        ];
        
        foreach ($roles as $role) {
            $this->message->setRole($role);
            $this->assertEquals($role, $this->message->getRole());
        }
    }

    public function testContentGetterAndSetter(): void
    {
        $content = '用户提问内容';
        $result = $this->message->setContent($content);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($content, $this->message->getContent());
    }

    public function testContentSetterWithEmptyString(): void
    {
        $result = $this->message->setContent('');
        
        $this->assertSame($this->message, $result);
        $this->assertEquals('', $this->message->getContent());
    }

    public function testContentSetterWithLongText(): void
    {
        $longContent = str_repeat('这是很长的文本内容。', 1000);
        $this->message->setContent($longContent);
        
        $this->assertEquals($longContent, $this->message->getContent());
    }

    public function testContentSetterWithSpecialCharacters(): void
    {
        $content = '特殊字符测试: #@$%^&*()_+ 🚀 💻 ✅';
        $this->message->setContent($content);
        
        $this->assertEquals($content, $this->message->getContent());
    }

    public function testAppendContent(): void
    {
        $this->message->setContent('初始内容');
        $this->message->appendContent('追加内容');
        
        $this->assertEquals('初始内容追加内容', $this->message->getContent());
    }

    public function testAppendContentMultipleTimes(): void
    {
        $this->message->setContent('开始');
        $this->message->appendContent(' - 中间');
        $this->message->appendContent(' - 结尾');
        
        $this->assertEquals('开始 - 中间 - 结尾', $this->message->getContent());
    }

    public function testAppendContentToEmptyContent(): void
    {
        $this->message->setContent('');
        $this->message->appendContent('新内容');
        
        $this->assertEquals('新内容', $this->message->getContent());
    }

    public function testReasoningContentGetterAndSetter(): void
    {
        $reasoning = '思考过程：这是推理内容';
        $result = $this->message->setReasoningContent($reasoning);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($reasoning, $this->message->getReasoningContent());
    }

    public function testReasoningContentSetterWithNull(): void
    {
        $result = $this->message->setReasoningContent(null);
        
        $this->assertSame($this->message, $result);
        $this->assertNull($this->message->getReasoningContent());
    }

    public function testAppendReasoningContent(): void
    {
        $this->message->setReasoningContent('初始推理');
        $this->message->appendReasoningContent('，继续推理');
        
        $this->assertEquals('初始推理，继续推理', $this->message->getReasoningContent());
    }

    public function testAppendReasoningContentToNullContent(): void
    {
        $this->message->setReasoningContent(null);
        $this->message->appendReasoningContent('新推理内容');
        
        $this->assertEquals('新推理内容', $this->message->getReasoningContent());
    }

    public function testModelGetterAndSetter(): void
    {
        $model = 'gpt-4';
        $result = $this->message->setModel($model);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($model, $this->message->getModel());
    }

    public function testModelSetterWithDifferentModels(): void
    {
        $models = [
            'gpt-3.5-turbo',
            'gpt-4',
            'deepseek-chat',
            'claude-3',
            'custom-model'
        ];
        
        foreach ($models as $model) {
            $this->message->setModel($model);
            $this->assertEquals($model, $this->message->getModel());
        }
    }

    public function testToolCallsGetterAndSetter(): void
    {
        $toolCalls = [
            [
                'id' => 'call_123',
                'function' => [
                    'name' => 'test_function',
                    'arguments' => '{"param": "value"}'
                ]
            ]
        ];
        
        $result = $this->message->setToolCalls($toolCalls);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($toolCalls, $this->message->getToolCalls());
    }

    public function testToolCallsSetterWithNull(): void
    {
        $result = $this->message->setToolCalls(null);
        
        $this->assertSame($this->message, $result);
        $this->assertNull($this->message->getToolCalls());
    }

    public function testAddToolCall(): void
    {
        $toolCall = [
            'id' => 'call_456',
            'function' => [
                'name' => 'another_function',
                'arguments' => '{"x": 1, "y": 2}'
            ]
        ];
        
        $this->message->addToolCall($toolCall);
        
        $toolCalls = $this->message->getToolCalls();
        $this->assertCount(1, $toolCalls);
        $this->assertEquals($toolCall, $toolCalls[0]);
    }

    public function testAddToolCallMultipleTimes(): void
    {
        $toolCall1 = ['id' => 'call_1', 'function' => ['name' => 'func1']];
        $toolCall2 = ['id' => 'call_2', 'function' => ['name' => 'func2']];
        
        $this->message->addToolCall($toolCall1);
        $this->message->addToolCall($toolCall2);
        
        $toolCalls = $this->message->getToolCalls();
        $this->assertCount(2, $toolCalls);
        $this->assertEquals($toolCall1, $toolCalls[0]);
        $this->assertEquals($toolCall2, $toolCalls[1]);
    }

    public function testAddToolCallToExistingArray(): void
    {
        $existing = [['id' => 'existing', 'function' => ['name' => 'existing_func']]];
        $this->message->setToolCalls($existing);
        
        $newCall = ['id' => 'new', 'function' => ['name' => 'new_func']];
        $this->message->addToolCall($newCall);
        
        $toolCalls = $this->message->getToolCalls();
        $this->assertCount(2, $toolCalls);
        $this->assertEquals($existing[0], $toolCalls[0]);
        $this->assertEquals($newCall, $toolCalls[1]);
    }

    public function testToolCallIdGetterAndSetter(): void
    {
        $toolCallId = 'call_test_789';
        $result = $this->message->setToolCallId($toolCallId);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($toolCallId, $this->message->getToolCallId());
    }

    public function testToolCallIdSetterWithNull(): void
    {
        $result = $this->message->setToolCallId(null);
        
        $this->assertSame($this->message, $result);
        $this->assertNull($this->message->getToolCallId());
    }

    public function testPromptTokensGetterAndSetter(): void
    {
        $tokens = 150;
        $result = $this->message->setPromptTokens($tokens);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($tokens, $this->message->getPromptTokens());
    }

    public function testPromptTokensDefaultValue(): void
    {
        $this->assertEquals(0, $this->message->getPromptTokens());
    }

    public function testPromptTokensWithBoundaryValues(): void
    {
        $values = [0, 1, 100, 1000, 4096, 8192];
        
        foreach ($values as $value) {
            $this->message->setPromptTokens($value);
            $this->assertEquals($value, $this->message->getPromptTokens());
        }
    }

    public function testCompletionTokensGetterAndSetter(): void
    {
        $tokens = 75;
        $result = $this->message->setCompletionTokens($tokens);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($tokens, $this->message->getCompletionTokens());
    }

    public function testCompletionTokensDefaultValue(): void
    {
        $this->assertEquals(0, $this->message->getCompletionTokens());
    }

    public function testCompletionTokensWithBoundaryValues(): void
    {
        $values = [0, 1, 50, 500, 2048, 4096];
        
        foreach ($values as $value) {
            $this->message->setCompletionTokens($value);
            $this->assertEquals($value, $this->message->getCompletionTokens());
        }
    }

    public function testTotalTokensGetterAndSetter(): void
    {
        $tokens = 225;
        $result = $this->message->setTotalTokens($tokens);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($tokens, $this->message->getTotalTokens());
    }

    public function testTotalTokensDefaultValue(): void
    {
        $this->assertEquals(0, $this->message->getTotalTokens());
    }

    public function testApiKeyGetterAndSetter(): void
    {
        $apiKey = $this->createMock(ApiKey::class);
        $result = $this->message->setApiKey($apiKey);
        
        $this->assertSame($this->message, $result);
        $this->assertSame($apiKey, $this->message->getApiKey());
    }

    public function testApiKeySetterWithNull(): void
    {
        $result = $this->message->setApiKey(null);
        
        $this->assertSame($this->message, $result);
        $this->assertNull($this->message->getApiKey());
    }

    public function testCreatedByGetterAndSetter(): void
    {
        $createdBy = 'user123';
        $result = $this->message->setCreatedBy($createdBy);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($createdBy, $this->message->getCreatedBy());
    }

    public function testCreatedBySetterWithNull(): void
    {
        $result = $this->message->setCreatedBy(null);
        
        $this->assertSame($this->message, $result);
        $this->assertNull($this->message->getCreatedBy());
    }

    public function testUpdatedByGetterAndSetter(): void
    {
        $updatedBy = 'admin456';
        $result = $this->message->setUpdatedBy($updatedBy);
        
        $this->assertSame($this->message, $result);
        $this->assertEquals($updatedBy, $this->message->getUpdatedBy());
    }

    public function testUpdatedBySetterWithNull(): void
    {
        $result = $this->message->setUpdatedBy(null);
        
        $this->assertSame($this->message, $result);
        $this->assertNull($this->message->getUpdatedBy());
    }

    public function testCreateTimeGetterAndSetter(): void
    {
        $dateTime = new DateTimeImmutable('2023-01-01 12:00:00');
        $this->message->setCreateTime($dateTime);
        
        $this->assertSame($dateTime, $this->message->getCreateTime());
    }

    public function testCreateTimeSetterWithNull(): void
    {
        $this->message->setCreateTime(null);
        
        $this->assertNull($this->message->getCreateTime());
    }

    public function testUpdateTimeGetterAndSetter(): void
    {
        $dateTime = new DateTimeImmutable('2023-01-02 15:30:00');
        $this->message->setUpdateTime($dateTime);
        
        $this->assertSame($dateTime, $this->message->getUpdateTime());
    }

    public function testUpdateTimeSetterWithNull(): void
    {
        $this->message->setUpdateTime(null);
        
        $this->assertNull($this->message->getUpdateTime());
    }

    public function testToArrayBasicMessage(): void
    {
        $this->message->setRole(RoleEnum::user);
        $this->message->setContent('Hello world');
        
        $array = $this->message->toArray();
        
        $expected = [
            'role' => 'user',
            'content' => 'Hello world'
        ];
        
        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithToolCalls(): void
    {
        $this->message->setRole(RoleEnum::assistant);
        $this->message->setContent('I will help you');
        
        $toolCalls = [
            [
                'id' => 'call_123',
                'function' => [
                    'name' => 'get_weather',
                    'arguments' => '{"location": "Beijing"}'
                ]
            ]
        ];
        $this->message->setToolCalls($toolCalls);
        
        $array = $this->message->toArray();
        
        $expected = [
            'role' => 'assistant',
            'content' => 'I will help you',
            'tool_calls' => $toolCalls
        ];
        
        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithToolCallId(): void
    {
        $this->message->setRole(RoleEnum::tool);
        $this->message->setContent('{"result": "success"}');
        $this->message->setToolCallId('call_456');
        
        $array = $this->message->toArray();
        
        $expected = [
            'role' => 'tool',
            'content' => '{"result": "success"}',
            'tool_call_id' => 'call_456'
        ];
        
        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithBothToolCallsAndToolCallId(): void
    {
        $this->message->setRole(RoleEnum::assistant);
        $this->message->setContent('Complete response');
        
        $toolCalls = [['id' => 'call_1', 'function' => ['name' => 'func1']]];
        $this->message->setToolCalls($toolCalls);
        $this->message->setToolCallId('call_response');
        
        $array = $this->message->toArray();
        
        $expected = [
            'role' => 'assistant',
            'content' => 'Complete response',
            'tool_calls' => $toolCalls,
            'tool_call_id' => 'call_response'
        ];
        
        $this->assertEquals($expected, $array);
    }

    public function testCompleteMessageWorkflow(): void
    {
        // 创建完整的消息
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        
        $this->message->setMsgId('msg_complete_test');
        $this->message->setConversation($conversation);
        $this->message->setRole(RoleEnum::assistant);
        $this->message->setContent('完整的AI回复');
        $this->message->setReasoningContent('思考过程');
        $this->message->setModel('gpt-4');
        $this->message->setPromptTokens(100);
        $this->message->setCompletionTokens(50);
        $this->message->setTotalTokens(150);
        $this->message->setApiKey($apiKey);
        $this->message->setCreatedBy('user123');
        
        $createTime = new DateTimeImmutable();
        $this->message->setCreateTime($createTime);
        
        // 验证所有属性
        $this->assertEquals('msg_complete_test', $this->message->getMsgId());
        $this->assertSame($conversation, $this->message->getConversation());
        $this->assertEquals(RoleEnum::assistant, $this->message->getRole());
        $this->assertEquals('完整的AI回复', $this->message->getContent());
        $this->assertEquals('思考过程', $this->message->getReasoningContent());
        $this->assertEquals('gpt-4', $this->message->getModel());
        $this->assertEquals(100, $this->message->getPromptTokens());
        $this->assertEquals(50, $this->message->getCompletionTokens());
        $this->assertEquals(150, $this->message->getTotalTokens());
        $this->assertSame($apiKey, $this->message->getApiKey());
        $this->assertEquals('user123', $this->message->getCreatedBy());
        $this->assertSame($createTime, $this->message->getCreateTime());
        $this->assertEquals('完整的AI回复', (string) $this->message);
    }

    public function testTokenCalculationScenario(): void
    {
        // 测试令牌计算场景
        $this->message->setPromptTokens(200);
        $this->message->setCompletionTokens(100);
        $this->message->setTotalTokens(300);
        
        // 验证令牌总数应该等于输入+输出
        $this->assertEquals(
            $this->message->getPromptTokens() + $this->message->getCompletionTokens(),
            $this->message->getTotalTokens()
        );
    }

    public function testUnicodeContentHandling(): void
    {
        $unicodeContent = '测试Unicode内容 🚀 💻 ✅ 中英混合 English mixed';
        $this->message->setContent($unicodeContent);
        
        $this->assertEquals($unicodeContent, $this->message->getContent());
        $this->assertEquals($unicodeContent, (string) $this->message);
    }

    public function testJsonContentInToolCall(): void
    {
        $jsonContent = '{"status": "success", "data": {"items": [1, 2, 3], "count": 3}}';
        $this->message->setContent($jsonContent);
        $this->message->setRole(RoleEnum::tool);
        
        $array = $this->message->toArray();
        
        $this->assertEquals('tool', $array['role']);
        $this->assertEquals($jsonContent, $array['content']);
    }
} 