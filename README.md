# OpenAIBundle

OpenAIBundle 是一个用于与 DeepSeek API 交互的 Symfony Bundle。它提供了一套完整的工具，用于进行 AI 对话、代码生成等任务。

## 功能特性

- 支持多种 DeepSeek 模型（deepseek-coder、deepseek-chat、deepseek-math、deepseek-chinese）
- 流式响应，实时输出 AI 回复
- 支持对话历史记录
- 支持系统提示词设置
- 提供命令行工具进行交互

## 配置

1. 在数据库中创建 `deepseek_config` 表，包含以下字段：
   - `id`: 配置ID
   - `base_url`: DeepSeek API 基础URL
   - `api_key`: DeepSeek API 密钥

2. 添加配置记录：

```sql
INSERT INTO deepseek_config (base_url, api_key) VALUES ('https://api.deepseek.com', 'your-api-key');
```

## 使用方法

### 命令行工具

1. 基本使用：

```bash
php bin/console deepseek:reason "你的问题" -c 1
```

2. 交互模式：

```bash
php bin/console deepseek:reason -i -c 1
```

3. 设置系统提示词：

```bash
php bin/console deepseek:reason -s "你是一个专业的PHP开发者" -c 1
```

4. 选择模型：

```bash
php bin/console deepseek:reason -m deepseek-chat -c 1
```

### 在代码中使用

```php
use OpenAIBundle\Service\OpenAiService;
use OpenAIBundle\Repository\ApiKeyRepository;

class YourService
{
    public function __construct(
        private readonly OpenAiService $deepSeekService,
        private readonly ApiKeyRepository $configRepository
    ) {
    }

    public function chat(): void
    {
        $config = $this->configRepository->find(1);
        $messages = [
            ['role' => 'system', 'content' => '你是一个专业的PHP开发者'],
            ['role' => 'user', 'content' => '如何使用 Symfony 创建自定义命令？']
        ];

        foreach ($this->deepSeekService->streamReasoner(
            $config,
            $messages,
            ['model' => 'deepseek-chat']
        ) as $chunk) {
            if ($chunk instanceof StreamChunkVO) {
                echo $chunk->getContent();
            }
        }
    }
}
```

## 异常处理

Bundle 定义了以下异常类：

- `DeepSeekException`: 基础异常类
- `ConfigurationException`: 配置相关异常
- `ModelException`: 模型相关异常

建议在使用时捕获这些异常并适当处理：

```php
try {
    $result = $this->deepSeekService->streamReasoner(...);
} catch (ConfigurationException $e) {
    // 处理配置错误
} catch (ModelException $e) {
    // 处理模型错误
} catch (DeepSeekException $e) {
    // 处理其他错误
}
```

## 贡献

欢迎提交 Issue 和 Pull Request。

## 许可证

MIT
