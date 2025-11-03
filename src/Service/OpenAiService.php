<?php

namespace OpenAIBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\VO\StreamChunkVO;
use OpenAIBundle\VO\StreamRequestOptions;
use OpenAIBundle\VO\StreamResponseVO;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Yiisoft\Json\Json;

#[WithMonologChannel(channel: 'open_ai')]
class OpenAiService
{
    private const DEFAULT_MODEL = 'deepseek-coder';

    private HttpClientInterface $httpClient;

    public function __construct(private readonly LoggerInterface $logger)
    {
        $this->httpClient = new NativeHttpClient();
    }

    /**
     * 非流式对话，直接返回完整响应
     *
     * @param ApiKey               $apiKey   配置
     * @param array<mixed>         $messages 历史消息
     * @param StreamRequestOptions $options  选项
     *
     * @return StreamResponseVO 完整的响应对象
     */
    public function chat(
        ApiKey $apiKey,
        array $messages,
        StreamRequestOptions $options,
    ): StreamResponseVO {
        $url = $apiKey->getChatCompletionUrl();

        // 合并消息和选项，生成请求体（非流式不需要 stream 参数）
        $requestBody = array_merge(
            ['messages' => $messages],
            $options->toRequestArray(self::DEFAULT_MODEL),
            ['stream' => false]
        );

        $requestOptions = $this->getRequestOptions($apiKey, $requestBody);

        $startTime = microtime(true);
        $this->logger->info('OpenAI API 请求开始', [
            'url' => $url,
            'model' => $requestBody['model'] ?? self::DEFAULT_MODEL,
            'message_count' => count($messages),
        ]);

        try {
            $response = $this->httpClient->request('POST', $url, $requestOptions);
            $responseData = Json::decode($response->getContent());

            $duration = microtime(true) - $startTime;
            $this->logger->info('OpenAI API 请求成功', [
                'duration' => $duration,
                'status_code' => $response->getStatusCode(),
                'usage' => is_array($responseData) && isset($responseData['usage']) ? $responseData['usage'] : null,
            ]);

            assert(is_array($responseData), 'Response data must be an array');
            /** @var array<string, mixed> $typedData */
            $typedData = $responseData;
            return StreamChunkVO::fromArray($typedData);
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logger->error('OpenAI API 请求失败', [
                'duration' => $duration,
                'exception' => $e->getMessage(),
                'url' => $url,
            ]);
            throw $e;
        }
    }

    /**
     * 流式对话，使用生成器返回响应内容
     *
     * @param ApiKey               $apiKey   配置
     * @param array<mixed>         $messages 历史消息
     * @param StreamRequestOptions $options  选项（可以是 VO 对象或数组，数组会自动转换为 VO）
     *
     * @return \Generator<StreamChunkVO> 生成器，每次产出一个响应块或调试信息
     */
    public function streamReasoner(
        ApiKey $apiKey,
        array $messages,
        StreamRequestOptions $options,
    ): \Generator {
        $url = $apiKey->getChatCompletionUrl();
        $requestBody = $this->buildRequestBody($messages, $options);
        $requestOptions = $this->getRequestOptions($apiKey, $requestBody);

        $response = $this->executeStreamRequest($url, $requestOptions, $requestBody, $messages);

        foreach ($this->processStreamResponse($response, $apiKey) as $chunk) {
            assert($chunk instanceof StreamChunkVO, 'Chunk must be a StreamChunkVO instance');
            yield $chunk;
        }
    }

    /**
     * @param array<mixed> $messages
     * @return array<mixed>
     */
    private function buildRequestBody(array $messages, StreamRequestOptions $options): array
    {
        return array_merge(
            ['messages' => $messages],
            $options->toRequestArray(self::DEFAULT_MODEL)
        );
    }

    /**
     * @param array<mixed> $requestOptions
     * @param array<mixed> $requestBody
     * @param array<mixed> $messages
     */
    private function executeStreamRequest(string $url, array $requestOptions, array $requestBody, array $messages): ResponseInterface
    {
        $startTime = microtime(true);

        $this->logger->info('OpenAI 流式 API 请求开始', [
            'url' => $url,
            'model' => $requestBody['model'] ?? self::DEFAULT_MODEL,
            'message_count' => count($messages),
        ]);

        try {
            return $this->httpClient->request('POST', $url, $requestOptions);
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logger->error('OpenAI 流式 API 请求失败', [
                'duration' => $duration,
                'exception' => $e->getMessage(),
                'url' => $url,
            ]);
            throw $e;
        }
    }

    private function processStreamResponse(ResponseInterface $response, ApiKey $apiKey): \Generator
    {
        $buffer = '';

        foreach ($this->httpClient->stream($response) as $chunk) {
            if ($chunk->isLast()) {
                break;
            }

            $processResult = $this->processChunk($chunk, $buffer, $apiKey);
            $buffer = $processResult['buffer'];
            $result = $processResult['result'];

            if (null === $result) {
                continue;
            }

            if ('done' === $result) {
                break;
            }

            yield $result;
        }
    }

    /**
     * @param mixed $chunk
     * @return array{result: mixed, buffer: string}
     */
    private function processChunk($chunk, string $buffer, ApiKey $apiKey): array
    {
        assert($chunk instanceof ChunkInterface, 'Chunk must be a ChunkInterface instance');

        $content = $chunk->getContent();
        if ('' === $content) {
            return ['result' => null, 'buffer' => $buffer];
        }

        $buffer .= $content;

        $processResult = $this->processBufferedLines($buffer, $apiKey);

        return ['result' => $processResult['result'], 'buffer' => $processResult['buffer']];
    }

    /**
     * @return array{result: mixed, buffer: string}
     */
    private function processBufferedLines(string $buffer, ApiKey $apiKey): array
    {
        while (($pos = strpos($buffer, "\n")) !== false) {
            $line = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + 1);

            $result = $this->processLine($line, $apiKey);
            if (null !== $result) {
                return ['result' => $result, 'buffer' => $buffer];
            }
        }

        return ['result' => null, 'buffer' => $buffer];
    }

    /**
     * @return mixed
     */
    private function processLine(string $line, ApiKey $apiKey)
    {
        if ('' === $line || !str_starts_with($line, 'data: ')) {
            return null;
        }

        try {
            $data = trim(substr($line, 6)); // Remove 'data: ' prefix

            if ('[DONE]' === $data) {
                return 'done';
            }

            $decoded = Json::decode($data);

            assert(is_array($decoded), 'Decoded data must be an array');
            /** @var array<string, mixed> $typedDecoded */
            $typedDecoded = $decoded;
            return StreamChunkVO::fromArray($typedDecoded);
        } catch (\Throwable $e) {
            $this->logger->error('流返回遇到异常', [
                'exception' => $e,
                'key' => $apiKey,
            ]);

            return null;
        }
    }

    /**
     * @param array<mixed> $json
     * @param array<string, string> $headers
     * @return array<mixed>
     */
    private function getRequestOptions(ApiKey $config, array $json = [], array $headers = []): array
    {
        return [
            'json' => $json,
            'headers' => array_merge([
                'Authorization' => sprintf('Bearer %s', $config->getApiKey()),
                'Content-Type' => 'application/json',
            ], $headers),
            'timeout' => 60,
        ];
    }
}
