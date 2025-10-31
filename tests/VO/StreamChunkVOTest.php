<?php

namespace OpenAIBundle\Tests\VO;

use OpenAIBundle\VO\ChoiceVO;
use OpenAIBundle\VO\StreamChunkVO;
use OpenAIBundle\VO\StreamResponseVO;
use OpenAIBundle\VO\UsageVO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StreamChunkVO::class)]
final class StreamChunkVOTest extends TestCase
{
    public function testFromArrayCreatesStreamChunkVOFromValidData(): void
    {
        $data = [
            'id' => 'chatcmpl-123',
            'created' => 1677649420,
            'model' => 'gpt-3.5-turbo',
            'system_fingerprint' => 'fp_abc123',
            'object' => 'chat.completion.chunk',
            'choices' => [
                [
                    'delta' => ['content' => 'Hello world'],
                    'finish_reason' => null,
                    'index' => 0,
                ],
            ],
            'usage' => [
                'prompt_tokens' => 12,
                'completion_tokens' => 10,
                'total_tokens' => 22,
            ],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);

        $this->assertEquals('chatcmpl-123', $streamChunk->id);
        $this->assertEquals(1677649420, $streamChunk->created);
        $this->assertEquals('gpt-3.5-turbo', $streamChunk->model);
        $this->assertEquals('fp_abc123', $streamChunk->systemFingerprint);
        $this->assertEquals('chat.completion.chunk', $streamChunk->object);
        $this->assertCount(1, $streamChunk->choices);
        $this->assertInstanceOf(UsageVO::class, $streamChunk->usage);
        $this->assertEquals($data, $streamChunk->getRawData());
    }

    public function testFromArrayHandlesMinimalData(): void
    {
        $data = [
            'id' => 'chatcmpl-minimal',
            'created' => 1677649420,
            'model' => 'gpt-3.5-turbo',
            'object' => 'chat.completion.chunk',
            'choices' => [],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);

        $this->assertEquals('chatcmpl-minimal', $streamChunk->id);
        $this->assertEquals('', $streamChunk->systemFingerprint);
        $this->assertCount(0, $streamChunk->choices);
        $this->assertNull($streamChunk->usage);
        $this->assertEquals($data, $streamChunk->getRawData());
    }

    public function testFromArrayHandlesMultipleChoices(): void
    {
        $data = [
            'id' => 'chatcmpl-multi',
            'created' => 1677649420,
            'model' => 'gpt-4',
            'object' => 'chat.completion.chunk',
            'choices' => [
                [
                    'delta' => ['content' => 'Choice 1'],
                    'finish_reason' => null,
                    'index' => 0,
                ],
                [
                    'delta' => ['content' => 'Choice 2'],
                    'finish_reason' => null,
                    'index' => 1,
                ],
            ],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);

        $choices = $streamChunk->getChoices();
        $this->assertCount(2, $choices);
        $this->assertInstanceOf(ChoiceVO::class, $choices[0]);
        $this->assertInstanceOf(ChoiceVO::class, $choices[1]);
    }

    public function testGetChoicesReturnsChoiceVOArray(): void
    {
        $data = [
            'id' => 'test',
            'created' => 1677649420,
            'model' => 'gpt-3.5-turbo',
            'object' => 'chat.completion.chunk',
            'choices' => [
                [
                    'delta' => ['content' => 'Test content'],
                    'finish_reason' => 'stop',
                    'index' => 0,
                ],
            ],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);
        $choices = $streamChunk->getChoices();
        $this->assertCount(1, $choices);
        $this->assertInstanceOf(ChoiceVO::class, $choices[0]);
        $this->assertEquals('Test content', $choices[0]->getContent());
    }

    public function testGetUsageReturnsUsageVOWhenPresent(): void
    {
        $data = [
            'id' => 'test',
            'created' => 1677649420,
            'model' => 'gpt-3.5-turbo',
            'object' => 'chat.completion.chunk',
            'choices' => [],
            'usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 50,
                'total_tokens' => 150,
            ],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);
        $usage = $streamChunk->getUsage();

        $this->assertInstanceOf(UsageVO::class, $usage);
        $this->assertEquals(100, $usage->getPromptTokens());
        $this->assertEquals(50, $usage->getCompletionTokens());
        $this->assertEquals(150, $usage->getTotalTokens());
    }

    public function testGetUsageReturnsNullWhenNotPresent(): void
    {
        $data = [
            'id' => 'test',
            'created' => 1677649420,
            'model' => 'gpt-3.5-turbo',
            'object' => 'chat.completion.chunk',
            'choices' => [],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);

        $this->assertNull($streamChunk->getUsage());
    }

    public function testGetRawDataReturnsOriginalData(): void
    {
        $data = [
            'id' => 'test',
            'created' => 1677649420,
            'model' => 'gpt-3.5-turbo',
            'object' => 'chat.completion.chunk',
            'choices' => [],
            'extra_field' => 'extra_value',
        ];

        $streamChunk = StreamChunkVO::fromArray($data);

        $this->assertEquals($data, $streamChunk->getRawData());
        $this->assertEquals('extra_value', $streamChunk->getRawData()['extra_field']);
    }

    public function testSetRawDataUpdatesRawData(): void
    {
        $originalData = [
            'id' => 'test',
            'created' => 1677649420,
            'model' => 'gpt-3.5-turbo',
            'object' => 'chat.completion.chunk',
            'choices' => [],
        ];

        $streamChunk = StreamChunkVO::fromArray($originalData);

        $newData = ['updated' => 'data'];
        $streamChunk->setRawData($newData);

        $this->assertEquals($newData, $streamChunk->getRawData());
        $this->assertNotEquals($originalData, $streamChunk->getRawData());
    }

    public function testInheritsFromStreamResponseVO(): void
    {
        $data = [
            'id' => 'test',
            'created' => 1677649420,
            'model' => 'gpt-3.5-turbo',
            'object' => 'chat.completion.chunk',
            'choices' => [],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);

        $this->assertInstanceOf(StreamResponseVO::class, $streamChunk);
    }

    public function testFromArrayHandlesComplexChoiceData(): void
    {
        $data = [
            'id' => 'chatcmpl-complex',
            'created' => 1677649420,
            'model' => 'gpt-4',
            'object' => 'chat.completion.chunk',
            'choices' => [
                [
                    'delta' => [
                        'content' => 'Complex response',
                        'role' => 'assistant',
                        'tool_calls' => [
                            [
                                'id' => 'call_123',
                                'function' => [
                                    'name' => 'get_weather',
                                    'arguments' => '{"location": "Beijing"}',
                                ],
                            ],
                        ],
                    ],
                    'finish_reason' => 'tool_calls',
                    'index' => 0,
                ],
            ],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);
        $choices = $streamChunk->getChoices();

        $this->assertCount(1, $choices);
        $this->assertEquals('Complex response', $choices[0]->getContent());
        $this->assertEquals('tool_calls', $choices[0]->getFinishReason());
        $this->assertNotNull($choices[0]->getToolCalls());
    }

    public function testFromArrayWithEmptySystemFingerprint(): void
    {
        $data = [
            'id' => 'test',
            'created' => 1677649420,
            'model' => 'gpt-3.5-turbo',
            'object' => 'chat.completion.chunk',
            'choices' => [],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);

        $this->assertEquals('', $streamChunk->systemFingerprint);
        $this->assertEquals('test', $streamChunk->id);
        $this->assertEquals('gpt-3.5-turbo', $streamChunk->model);
    }

    public function testMsgIdDerivedFromId(): void
    {
        $data = [
            'id' => 'test-msg-id',
            'created' => 1677649420,
            'model' => 'gpt-3.5-turbo',
            'object' => 'chat.completion.chunk',
            'choices' => [],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);

        $this->assertEquals('test-msg-id', $streamChunk->getMsgId());
        $this->assertEquals($streamChunk->id, $streamChunk->getMsgId());
    }

    public function testStreamChunkWithReasoningContent(): void
    {
        $data = [
            'id' => 'chatcmpl-reasoning',
            'created' => 1677649420,
            'model' => 'o1-preview',
            'object' => 'chat.completion.chunk',
            'choices' => [
                [
                    'delta' => [
                        'content' => 'Final answer',
                        'reasoning_content' => 'Let me think about this...',
                    ],
                    'finish_reason' => null,
                    'index' => 0,
                ],
            ],
        ];

        $streamChunk = StreamChunkVO::fromArray($data);
        $choices = $streamChunk->getChoices();

        $this->assertEquals('Final answer', $choices[0]->getContent());
        $this->assertEquals('Let me think about this...', $choices[0]->getReasoningContent());
    }

    public function testStreamChunkDataPersistence(): void
    {
        $originalData = [
            'id' => 'persist-test',
            'created' => 1677649420,
            'model' => 'gpt-4',
            'object' => 'chat.completion.chunk',
            'choices' => [],
            'metadata' => ['key' => 'value'],
        ];

        $streamChunk = StreamChunkVO::fromArray($originalData);

        $this->assertEquals('persist-test', $streamChunk->id);
        $this->assertEquals(1677649420, $streamChunk->created);
        $this->assertEquals('gpt-4', $streamChunk->model);
        $this->assertEquals($originalData, $streamChunk->getRawData());
    }
}
