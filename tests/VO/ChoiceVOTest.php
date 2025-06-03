<?php

namespace OpenAIBundle\Tests\VO;

use OpenAIBundle\VO\ChoiceVO;
use PHPUnit\Framework\TestCase;

class ChoiceVOTest extends TestCase
{
    public function test_fromArray_createsChoiceVOFromValidData(): void
    {
        $data = [
            'delta' => [
                'content' => 'Hello world',
                'role' => 'assistant'
            ],
            'finish_reason' => 'stop',
            'index' => 0
        ];

        $choice = ChoiceVO::fromArray($data);

        $this->assertEquals('Hello world', $choice->getContent());
        $this->assertEquals('assistant', $choice->getRole());
        $this->assertEquals('stop', $choice->getFinishReason());
        $this->assertEquals(0, $choice->getIndex());
    }

    public function test_fromArray_handlesEmptyDelta(): void
    {
        $data = [
            'delta' => [],
            'finish_reason' => null,
            'index' => 0
        ];

        $choice = ChoiceVO::fromArray($data);

        $this->assertNull($choice->getContent());
        $this->assertNull($choice->getRole());
        $this->assertNull($choice->getFinishReason());
        $this->assertEquals(0, $choice->getIndex());
    }

    public function test_getContent_returnsNullWhenNotSet(): void
    {
        $choice = new ChoiceVO([]);

        $this->assertNull($choice->getContent());
    }

    public function test_getContent_returnsContentFromDelta(): void
    {
        $choice = new ChoiceVO(['content' => 'Test content']);

        $this->assertEquals('Test content', $choice->getContent());
    }

    public function test_getReasoningContent_returnsNullWhenNotSet(): void
    {
        $choice = new ChoiceVO([]);

        $this->assertNull($choice->getReasoningContent());
    }

    public function test_getReasoningContent_returnsReasoningFromDelta(): void
    {
        $choice = new ChoiceVO(['reasoning_content' => 'Reasoning process']);

        $this->assertEquals('Reasoning process', $choice->getReasoningContent());
    }

    public function test_getRole_returnsNullWhenNotSet(): void
    {
        $choice = new ChoiceVO([]);

        $this->assertNull($choice->getRole());
    }

    public function test_getRole_returnsRoleFromDelta(): void
    {
        $choice = new ChoiceVO(['role' => 'user']);

        $this->assertEquals('user', $choice->getRole());
    }

    public function test_getFinishReason_handlesNullValue(): void
    {
        $choice = new ChoiceVO([], null);

        $this->assertNull($choice->getFinishReason());
    }

    public function test_getFinishReason_returnsFinishReason(): void
    {
        $choice = new ChoiceVO([], 'length');

        $this->assertEquals('length', $choice->getFinishReason());
    }

    public function test_getIndex_handlesNullValue(): void
    {
        $choice = new ChoiceVO([], null, null);

        $this->assertNull($choice->getIndex());
    }

    public function test_getIndex_returnsIndex(): void
    {
        $choice = new ChoiceVO([], null, 2);

        $this->assertEquals(2, $choice->getIndex());
    }

    public function test_getToolCalls_returnsNullWhenNotSet(): void
    {
        $choice = new ChoiceVO([]);

        $this->assertNull($choice->getToolCalls());
    }

    public function test_getToolCalls_returnsToolCallsFromDelta(): void
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

        $choice = new ChoiceVO(['tool_calls' => $toolCalls]);

        $this->assertEquals($toolCalls, $choice->getToolCalls());
    }

    public function test_getDecodeToolCalls_returnsEmptyArrayWhenNoToolCalls(): void
    {
        $choice = new ChoiceVO([]);

        $this->assertEquals([], $choice->getDecodeToolCalls());
    }

    public function test_getDecodeToolCalls_decodesToolCallsFromDelta(): void
    {
        $toolCalls = [
            [
                'id' => 'call_123',
                'index' => 0,
                'type' => 'function',
                'function' => [
                    'name' => 'test_function',
                    'arguments' => '{"param": "value"}'
                ]
            ]
        ];

        $choice = new ChoiceVO(['tool_calls' => $toolCalls]);
        $decodedCalls = $choice->getDecodeToolCalls();

        $this->assertCount(1, $decodedCalls);
        $this->assertEquals('call_123', $decodedCalls[0]->getId());
        $this->assertEquals('test_function', $decodedCalls[0]->getFunctionName());
        $this->assertEquals(['param' => 'value'], $decodedCalls[0]->getFunctionArguments());
    }

    public function test_getDecodeToolCalls_handlesInvalidJsonArguments(): void
    {
        $toolCalls = [
            [
                'id' => 'call_123',
                'index' => 0,
                'type' => 'function',
                'function' => [
                    'name' => 'test_function',
                    'arguments' => 'invalid json'
                ]
            ]
        ];

        $choice = new ChoiceVO(['tool_calls' => $toolCalls]);
        
        $this->expectException(\JsonException::class);
        $choice->getDecodeToolCalls();
    }

    public function test_fromArray_withMinimalData(): void
    {
        $data = ['delta' => []];

        $choice = ChoiceVO::fromArray($data);

        $this->assertNull($choice->getContent());
        $this->assertNull($choice->getRole());
        $this->assertNull($choice->getFinishReason());
        $this->assertNull($choice->getIndex());
    }

    public function test_fromArray_withCompleteData(): void
    {
        $data = [
            'delta' => [
                'content' => 'Complete response',
                'role' => 'assistant',
                'reasoning_content' => 'Thinking process',
                'tool_calls' => [
                    [
                        'id' => 'call_456',
                        'index' => 0,
                        'type' => 'function',
                        'function' => [
                            'name' => 'calculate',
                            'arguments' => '{"x": 5, "y": 3}'
                        ]
                    ]
                ]
            ],
            'finish_reason' => 'tool_calls',
            'index' => 1
        ];

        $choice = ChoiceVO::fromArray($data);

        $this->assertEquals('Complete response', $choice->getContent());
        $this->assertEquals('assistant', $choice->getRole());
        $this->assertEquals('Thinking process', $choice->getReasoningContent());
        $this->assertEquals('tool_calls', $choice->getFinishReason());
        $this->assertEquals(1, $choice->getIndex());
        $this->assertNotNull($choice->getToolCalls());
        $this->assertCount(1, $choice->getDecodeToolCalls());
    }
} 