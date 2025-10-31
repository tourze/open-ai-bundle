<?php

namespace OpenAIBundle\Tests\VO;

use OpenAIBundle\VO\StreamRequestOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StreamRequestOptions::class)]
final class StreamRequestOptionsTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $options = new StreamRequestOptions();

        $this->assertFalse($options->isDebug());
        $this->assertNull($options->getModel());
        $this->assertEquals(0.7, $options->getTemperature());
        $this->assertEquals(1.0, $options->getTopP());
        $this->assertEquals(2000, $options->getMaxTokens());
        $this->assertEquals(0.0, $options->getPresencePenalty());
        $this->assertEquals(0.0, $options->getFrequencyPenalty());
        $this->assertNull($options->getTools());
        $this->assertEmpty($options->getExtraOptions());
    }

    public function testConstructorWithCustomValues(): void
    {
        $tools = [
            ['type' => 'function', 'function' => ['name' => 'test']],
        ];
        $extraOptions = ['custom_field' => 'custom_value'];

        $options = new StreamRequestOptions(
            debug: true,
            model: 'gpt-4',
            temperature: 0.9,
            topP: 0.95,
            maxTokens: 4000,
            presencePenalty: 0.5,
            frequencyPenalty: 0.3,
            tools: $tools,
            extraOptions: $extraOptions,
        );

        $this->assertTrue($options->isDebug());
        $this->assertEquals('gpt-4', $options->getModel());
        $this->assertEquals(0.9, $options->getTemperature());
        $this->assertEquals(0.95, $options->getTopP());
        $this->assertEquals(4000, $options->getMaxTokens());
        $this->assertEquals(0.5, $options->getPresencePenalty());
        $this->assertEquals(0.3, $options->getFrequencyPenalty());
        $this->assertEquals($tools, $options->getTools());
        $this->assertEquals($extraOptions, $options->getExtraOptions());
    }

    public function testFromArrayWithAllFields(): void
    {
        $data = [
            'debug' => true,
            'model' => 'deepseek-coder',
            'temperature' => 0.8,
            'top_p' => 0.9,
            'max_tokens' => 3000,
            'presence_penalty' => 0.2,
            'frequency_penalty' => 0.1,
            'tools' => [['type' => 'function']],
            'custom_option' => 'custom_value',
        ];

        $options = StreamRequestOptions::fromArray($data);

        $this->assertTrue($options->isDebug());
        $this->assertEquals('deepseek-coder', $options->getModel());
        $this->assertEquals(0.8, $options->getTemperature());
        $this->assertEquals(0.9, $options->getTopP());
        $this->assertEquals(3000, $options->getMaxTokens());
        $this->assertEquals(0.2, $options->getPresencePenalty());
        $this->assertEquals(0.1, $options->getFrequencyPenalty());
        $this->assertEquals([['type' => 'function']], $options->getTools());
        $this->assertEquals(['custom_option' => 'custom_value'], $options->getExtraOptions());
    }

    public function testFromArrayWithPartialFields(): void
    {
        $data = [
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.5,
        ];

        $options = StreamRequestOptions::fromArray($data);

        $this->assertFalse($options->isDebug());
        $this->assertEquals('gpt-3.5-turbo', $options->getModel());
        $this->assertEquals(0.5, $options->getTemperature());
        $this->assertEquals(1.0, $options->getTopP());
        $this->assertEquals(2000, $options->getMaxTokens());
    }

    public function testToRequestArrayWithDefaultModel(): void
    {
        $options = new StreamRequestOptions(
            temperature: 0.8,
            maxTokens: 1500,
        );

        $requestArray = $options->toRequestArray('default-model');

        $this->assertEquals('default-model', $requestArray['model']);
        $this->assertEquals(0.8, $requestArray['temperature']);
        $this->assertEquals(1.0, $requestArray['top_p']);
        $this->assertEquals(1500, $requestArray['max_tokens']);
        $this->assertEquals(0.0, $requestArray['presence_penalty']);
        $this->assertEquals(0.0, $requestArray['frequency_penalty']);
        $this->assertTrue($requestArray['stream']);
        $this->assertEquals(['include_usage' => true], $requestArray['stream_options']);
        $this->assertArrayNotHasKey('tools', $requestArray);
    }

    public function testToRequestArrayWithCustomModel(): void
    {
        $options = new StreamRequestOptions(
            model: 'custom-model',
        );

        $requestArray = $options->toRequestArray('default-model');

        $this->assertEquals('custom-model', $requestArray['model']);
    }

    public function testToRequestArrayWithTools(): void
    {
        $tools = [
            ['type' => 'function', 'function' => ['name' => 'test_function']],
        ];
        $options = new StreamRequestOptions(tools: $tools);

        $requestArray = $options->toRequestArray();

        $this->assertArrayHasKey('tools', $requestArray);
        $this->assertEquals($tools, $requestArray['tools']);
    }

    public function testToRequestArrayWithExtraOptions(): void
    {
        $options = new StreamRequestOptions(
            extraOptions: ['stop' => ["\n"], 'n' => 2],
        );

        $requestArray = $options->toRequestArray();

        $this->assertArrayHasKey('stop', $requestArray);
        $this->assertEquals(["\n"], $requestArray['stop']);
        $this->assertArrayHasKey('n', $requestArray);
        $this->assertEquals(2, $requestArray['n']);
    }

    public function testSettersAndGetters(): void
    {
        $options = new StreamRequestOptions();

        $options->setDebug(true);
        $this->assertTrue($options->isDebug());

        $options->setModel('test-model');
        $this->assertEquals('test-model', $options->getModel());

        $options->setTemperature(0.6);
        $this->assertEquals(0.6, $options->getTemperature());

        $options->setTopP(0.85);
        $this->assertEquals(0.85, $options->getTopP());

        $options->setMaxTokens(2500);
        $this->assertEquals(2500, $options->getMaxTokens());

        $options->setPresencePenalty(0.4);
        $this->assertEquals(0.4, $options->getPresencePenalty());

        $options->setFrequencyPenalty(0.2);
        $this->assertEquals(0.2, $options->getFrequencyPenalty());

        $tools = [['type' => 'function']];
        $options->setTools($tools);
        $this->assertEquals($tools, $options->getTools());

        $extraOptions = ['extra' => 'value'];
        $options->setExtraOptions($extraOptions);
        $this->assertEquals($extraOptions, $options->getExtraOptions());
    }

    public function testFluentInterface(): void
    {
        $options = new StreamRequestOptions();
        $options->setDebug(true);
        $options->setModel('gpt-4');
        $options->setTemperature(0.9);
        $options->setMaxTokens(3000);

        $this->assertTrue($options->isDebug());
        $this->assertEquals('gpt-4', $options->getModel());
        $this->assertEquals(0.9, $options->getTemperature());
        $this->assertEquals(3000, $options->getMaxTokens());
    }
}
