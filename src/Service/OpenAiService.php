<?php

namespace OpenAIBundle\Service;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\VO\StreamChunkVO;
use OpenAIBundle\VO\StreamRequestOptions;
use OpenAIBundle\VO\StreamResponseVO;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Yiisoft\Json\Json;

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
     * @param ApiKey                $apiKey   配置
     * @param array                 $messages 历史消息
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

        $response = $this->httpClient->request('POST', $url, $requestOptions);
        
        $responseData = Json::decode($response->getContent());
        
        return StreamResponseVO::fromArray($responseData);
    }

    /**
     * 流式对话，使用生成器返回响应内容
     *
     * @param ApiKey                     $apiKey   配置
     * @param array                      $messages 历史消息
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

        // 合并消息和选项，生成请求体
        $requestBody = array_merge(
            ['messages' => $messages],
            $options->toRequestArray(self::DEFAULT_MODEL)
        );

        $requestOptions = $this->getRequestOptions($apiKey, $requestBody);

        $response = $this->httpClient->request('POST', $url, $requestOptions);

        $buffer = '';
        $isDone = false;

        foreach ($this->httpClient->stream($response) as $chunk) {
            if ($chunk->isLast() || $isDone) {
                break;
            }

            $content = $chunk->getContent();
            if (empty($content)) {
                continue;
            }

            $buffer .= $content;
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                // dump($line);

                if (empty($line) || !str_starts_with($line, 'data: ')) {
                    continue;
                }

                try {
                    $data = trim(substr($line, 6)); // Remove 'data: ' prefix
                    if ('[DONE]' === $data) {
                        $isDone = true;
                        break;
                    }

                    $decoded = Json::decode($data);
                    yield StreamChunkVO::fromArray($decoded);
                } catch (\Throwable $e) {
                    $this->logger->error('流返回遇到异常', [
                        'exception' => $e,
                        'key' => $apiKey,
                    ]);
                    continue;
                }
            }
        }
    }

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
