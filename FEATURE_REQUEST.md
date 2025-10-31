# OpenAIBundle 功能需求文档

## 概述

为了支持 open-ai-http-proxy-bundle 的完整功能实现，我们需要对 OpenAIBundle 进行以下功能增强。

## 功能需求清单

### 1. API Key 池管理增强

#### 1.1 多供应商支持

**需求描述**：
扩展当前的 API Key 管理，支持多种 AI 服务提供商。

**具体要求**：
- 在 `ApiKey` 实体中添加 `provider` 字段（枚举类型）
- 支持的供应商：`openai`, `azure`, `anthropic`, `google`, `baidu`, `alibaba`
- 每个供应商可能需要不同的认证方式和请求格式

**数据模型扩展**：
```php
/**
 * @ORM\Column(type="string", length=50)
 */
private string $provider = 'openai';

/**
 * @ORM\Column(type="json", nullable=true)
 */
private ?array $customHeaders = null;

/**
 * @ORM\Column(type="json", nullable=true)
 */
private ?array $providerConfig = null; // Azure需要deployment_id等
```

#### 1.2 Key 健康状态管理

**需求描述**：
添加 API Key 的健康检查和状态跟踪机制。

**具体要求**：
- 添加健康状态字段：`health_status`（healthy/unhealthy/unknown）
- 添加最后检查时间：`last_health_check`
- 添加错误计数器：`error_count`、`success_count`
- 添加速率限制信息：`rate_limit`、`rate_limit_remaining`

#### 1.3 Key 使用统计

**需求描述**：
记录每个 Key 的详细使用统计。

**具体要求**：
- Token 消耗统计：`total_prompt_tokens`、`total_completion_tokens`
- 成本统计：`total_cost`（根据模型价格计算）
- 请求统计：`total_requests`、`failed_requests`
- 最后使用时间：`last_used_at`

### 2. 模型管理功能

#### 2.1 模型配置实体

**需求描述**：
创建独立的模型配置实体，管理不同供应商的模型信息。

**数据模型**：
```php
class Model {
    private string $id;
    private string $name;           // 显示名称
    private string $modelId;         // 实际模型ID
    private string $provider;        // 供应商
    private float $promptPrice;      // 每1K token价格
    private float $completionPrice;  // 每1K token价格
    private int $maxTokens;          // 最大token数
    private int $contextWindow;      // 上下文窗口大小
    private bool $supportStreaming;  // 是否支持流式
    private bool $supportFunctions;  // 是否支持函数调用
    private array $capabilities;     // 能力标签
}
```

#### 2.2 模型映射规则

**需求描述**：
支持动态的模型名称映射。

**具体要求**：
- 创建 `ModelMapping` 实体
- 支持基于条件的映射（时间、负载、成本）
- 支持降级和升级策略

### 3. 批量操作支持

#### 3.1 批量Key验证

**需求描述**：
提供批量验证多个 API Key 的功能。

**接口定义**：
```php
public function validateKeys(array $keyIds): array;
public function healthCheckAll(): array;
```

#### 3.2 批量状态更新

**需求描述**：
支持批量启用/禁用 Key。

**接口定义**：
```php
public function batchUpdateStatus(array $keyIds, bool $enabled): int;
public function batchResetCounters(array $keyIds): int;
```

### 4. 服务层增强

#### 4.1 KeyPoolService

**需求描述**：
创建专门的 Key 池管理服务。

**主要功能**：
```php
interface KeyPoolServiceInterface {
    public function getAvailableKeys(string $provider = null): array;
    public function getKeysByTags(array $tags): array;
    public function getHealthyKeys(): array;
    public function markKeyAsUnhealthy(int $keyId, string $reason): void;
    public function rotateKeys(): void; // 轮换策略
}
```

#### 4.2 ModelService

**需求描述**：
创建模型管理服务。

**主要功能**：
```php
interface ModelServiceInterface {
    public function getModelByName(string $name): ?Model;
    public function getSupportedModels(string $provider): array;
    public function mapModel(string $source, array $context = []): string;
    public function calculateCost(string $model, int $promptTokens, int $completionTokens): float;
}
```

### 5. 事件系统

#### 5.1 Key 事件

**需求描述**：
添加 Key 相关的事件。

**事件列表**：
- `KeyHealthCheckFailedEvent`
- `KeyQuotaExceededEvent`
- `KeyRateLimitReachedEvent`
- `KeyExpiredEvent`

#### 5.2 使用事件

**需求描述**：
添加使用相关的事件。

**事件列表**：
- `ApiCallStartedEvent`
- `ApiCallCompletedEvent`
- `ApiCallFailedEvent`
- `TokensConsumedEvent`

### 6. 命令行工具

#### 6.1 Key 管理命令

```bash
# 健康检查
php bin/console open-ai:key:health-check [--key=KEY_ID]

# 批量导入
php bin/console open-ai:key:import keys.csv

# 使用报告
php bin/console open-ai:key:report --from=2024-01-01 --to=2024-01-31
```

#### 6.2 模型管理命令

```bash
# 同步模型列表
php bin/console open-ai:model:sync --provider=openai

# 测试模型
php bin/console open-ai:model:test MODEL_NAME
```

### 7. Admin 界面增强

#### 7.1 Key 管理界面

**需求**：
- 批量操作按钮（启用/禁用/删除）
- 健康状态指示器（绿/黄/红）
- 使用统计图表
- 快速测试按钮

#### 7.2 模型管理界面

**需求**：
- 模型列表和配置
- 映射规则管理
- 成本计算器

### 8. 配置文件支持

**需求描述**：
支持通过配置文件管理 Key 和模型。

**配置示例**：
```yaml
open_ai:
  keys:
    - provider: openai
      api_key: '%env(OPENAI_KEY_1)%'
      base_url: 'https://api.openai.com/v1/'
      tags: ['production', 'gpt-4']
      
    - provider: azure
      api_key: '%env(AZURE_KEY_1)%'
      base_url: '%env(AZURE_ENDPOINT)%'
      config:
        deployment_id: 'gpt-4-deployment'
        api_version: '2024-02-15-preview'
        
  models:
    - name: 'gpt-4'
      provider: 'openai'
      prompt_price: 0.03
      completion_price: 0.06
      max_tokens: 8192
```

## 实施优先级

### P0 - 必须（第一阶段）
1. 多供应商支持
2. Key 健康状态管理
3. KeyPoolService
4. 批量Key验证

### P1 - 重要（第二阶段）
1. 模型管理功能
2. 使用统计
3. 事件系统
4. 健康检查命令

### P2 - 可选（第三阶段）
1. Admin界面增强
2. 配置文件支持
3. 高级报告功能

## 向后兼容性

所有新功能必须保持向后兼容：
- 新字段使用默认值
- 新接口作为可选服务
- 保留原有API不变

## 测试要求

- 单元测试覆盖率 >90%
- 集成测试覆盖所有供应商
- 性能测试（批量操作）

## 交付标准

1. 代码符合 PSR-12 标准
2. 完整的 PHPDoc 注释
3. 更新 README 文档
4. 提供迁移脚本
5. 通过 PHPStan Level 8

## 时间估算

- 第一阶段（P0）：2周
- 第二阶段（P1）：2周
- 第三阶段（P2）：1周

总计：5周

## 依赖关系

- 无外部依赖
- 需要协调 open-ai-http-proxy-bundle 的开发进度

---

**文档版本**：1.0  
**创建日期**：2024-01-20  
**状态**：待审核