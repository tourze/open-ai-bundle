<?php

namespace OpenAIBundle\Service;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\VO\StreamChunkVO;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Yiisoft\Json\Json;

class OpenAiService
{
    private const SUPPORTED_MODELS = [
        'deepseek-coder',
        'deepseek-chat',
        'deepseek-math',
        'deepseek-chinese',
    ];

    private const DEFAULT_MODEL = 'deepseek-coder';

    private HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = new NativeHttpClient();
    }

    /**
     * 流式对话，使用生成器返回响应内容
     *
     * @param ApiKey $apiKey   配置
     * @param array  $messages 历史消息
     * @param array  $options  选项
     *
     * @return \Generator<StreamChunkVO|string> 生成器，每次产出一个响应块或调试信息
     *
     * @throws \RuntimeException
     */
    public function streamReasoner(
        ApiKey $apiKey,
        array $messages = [],
        array $options = [],
    ): \Generator {
        $url = $apiKey->getChatCompletionUrl();

        // 从用户选项中分离出 API 请求选项和调试选项
        $debug = $options['debug'] ?? false;
        unset($options['debug']);

        // 验证并设置模型
        $model = $options['model'] ?? self::DEFAULT_MODEL;
        unset($options['model']);

        // 合并默认选项和用户提供的选项
        $requestBody = array_merge([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'stream' => true,
            'stream_options' => [
                'include_usage' => true,
            ],
        ], $options);

        $requestOptions = $this->getRequestOptions($apiKey, $requestBody);

        // 仅在开发环境显示调试信息
        if ($debug) {
            // 为了安全起见，在显示之前移除敏感信息
            $debugOptions = $requestOptions;
            $debugOptions['headers']['Authorization'] = 'Bearer ****';
            yield sprintf(
                "\n调试信息：\n请求 URL: %s\n请求选项：%s\n",
                $url,
                Json::encode($debugOptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

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
                        if ($debug) {
                            yield "\n请求结束\n";
                        }
                        $isDone = true;
                        break;
                    }

                    $decoded = Json::decode($data);
                    yield StreamChunkVO::fromArray($decoded);
                } catch (\Throwable $e) {
                    if ($debug) {
                        yield sprintf("\n解析错误：%s\n", $e);
                    }
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

    public function getSupportedModels(): array
    {
        return self::SUPPORTED_MODELS;
    }
}
