<?php

namespace OpenAIBundle\Tests\VO;

use OpenAIBundle\VO\StreamChunkVO;
use OpenAIBundle\VO\StreamResponseVO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StreamResponseVO::class)]
final class StreamResponseVOTest extends TestCase
{
    public function testCreateStreamResponseVO(): void
    {
        $id = 'test-id';
        $created = time();
        $model = 'gpt-4';
        $systemFingerprint = 'test-fingerprint';
        $object = 'chat.completion';
        $choices = [];
        $usage = null;

        $response = new StreamChunkVO($id, $created, $model, $systemFingerprint, $object, $choices, $usage);

        $this->assertInstanceOf(StreamResponseVO::class, $response);
        $this->assertEquals($id, $response->id);
        $this->assertEquals($object, $response->object);
        $this->assertEquals($created, $response->created);
        $this->assertEquals($model, $response->model);
        $this->assertEquals($choices, $response->choices);
        $this->assertEquals($usage, $response->usage);
    }

    public function testGetMsgId(): void
    {
        $id = 'test-id';
        $response = new StreamChunkVO($id, time(), 'gpt-4', 'fingerprint', 'chat.completion', [], null);

        $this->assertEquals($id, $response->getMsgId());
    }
}
