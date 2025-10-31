# OpenAIBundle

[English](README.md) | [中文](README.zh-CN.md)

[![最新版本](https://img.shields.io/packagist/v/tourze/open-ai-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/open-ai-bundle)
[![下载量](https://img.shields.io/packagist/dt/tourze/open-ai-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/open-ai-bundle)
[![许可证](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP 版本](https://img.shields.io/badge/php-%5E8.1-blue.svg?style=flat-square)](https://www.php.net/)
[![构建状态](https://img.shields.io/github/workflow/status/tourze/php-monorepo/CI?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![代码覆盖率](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

OpenAIBundle 是一个用于与 DeepSeek API 交互的 Symfony Bundle。
它提供了一套完整的工具，用于进行 AI 对话、代码生成等任务。

## 目录

- [功能特性](#功能特性)
- [系统要求](#系统要求)
- [安装](#安装)
- [快速开始](#快速开始)
- [配置](#配置)
- [使用方法](#使用方法)
- [高级用法](#高级用法)
- [安全性](#安全性)
- [依赖关系](#依赖关系)
- [贡献](#贡献)
- [许可证](#许可证)

## 功能特性

- 支持多种 DeepSeek 模型（deepseek-coder、deepseek-chat、deepseek-math、deepseek-chinese）
- 流式响应，实时输出 AI 回复
- 支持对话历史记录
- 支持系统提示词设置
- 提供命令行工具进行交互
- 支持函数调用（Function Calling）
- 支持多角色管理
- 支持思维链（Chain of Thought）输出

## 系统要求

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- Doctrine ORM 3.0 或更高版本

## 安装

```bash
composer require tourze/open-ai-bundle
```

## 快速开始

### 1. 配置 API 密钥

在 EasyAdmin 界面中添加 API 密钥：

- 访问 `/admin/api-key`
- 添加新的 API 密钥
- 配置模型和端点 URL

### 2. 创建角色

在 EasyAdmin 界面中创建 AI 角色：

- 访问 `/admin/character`
- 设置角色名称和系统提示词
- 关联 API 密钥

### 3. 开始对话

```bash
php bin/console open-ai:chat -c 1
```

## 配置

1. 在数据库中创建相关表（使用 Doctrine 迁移）
2. 配置 API 密钥和角色

## 使用方法

### 命令行工具

#### open-ai:chat 命令

用于与 AI 模型进行对话的命令，支持交互模式和单次执行模式。

1. 交互模式：

```bash
# 基本交互模式
php bin/console open-ai:chat -c 1

# 使用非流式响应
php bin/console open-ai:chat -c 1 -s

# 指定特定的 API 密钥
php bin/console open-ai:chat -c 1 -k 2
```

2. 单次执行模式：

```bash
# 单次执行
php bin/console open-ai:chat -c 1 -p "写一首诗"

# 单次执行，静默模式（只输出 AI 响应）
php bin/console open-ai:chat -c 1 -p "写一首诗" -q

# 单次执行，非流式，静默模式
php bin/console open-ai:chat -c 1 -p "写一首诗" -s -q
```

**参数说明：**
- `-c, --character`: 角色ID（必选）
- `-k, --api-key`: API密钥ID（可选，优先使用角色的默认密钥）
- `-p, --prompt`: 直接指定提示词，启用单次执行模式
- `-s, --no-stream`: 使用非流式模式（默认为流式）
- `-q, --quiet`: 静默模式，只输出AI响应内容，无其他信息
- `-d, --debug`: 开启调试模式

**交互模式指令：**
- 输入 `q`、`quit` 或 `exit` 退出
- 输入 `c` 或 `clear` 清除对话历史

## 异常处理

Bundle 定义了以下异常类：

- `DeepSeekException`: 基础异常类
- `ConfigurationException`: 配置相关异常
- `ModelException`: 模型相关异常

建议在使用时捕获这些异常并适当处理：

```php
try {
    $result = $this->openAiService->streamReasoner(...);
} catch (ConfigurationException $e) {
    // 处理配置错误
} catch (ModelException $e) {
    // 处理模型错误
} catch (DeepSeekException $e) {
    // 处理其他错误
}
```

## 高级用法

### 自定义 AI 函数

通过实现 `AiFunctionInterface` 创建自定义 AI 函数：

```php
use OpenAIBundle\AiFunction\AiFunctionInterface;

class CustomFunction implements AiFunctionInterface
{
    public function getDefinition(): FunctionDefinition
    {
        return new FunctionDefinition(
            'custom_function',
            '自定义函数描述',
            [
                new FunctionParam('param_name', FunctionParamType::STRING, '参数描述', true)
            ]
        );
    }

    public function execute(array $arguments): string
    {
        // 自定义逻辑
        return json_encode(['result' => 'success']);
    }
}
```

### 流式处理

处理流式响应：

```php
foreach ($openAiService->streamReasoner($apiKey, $messages, $options) as $chunk) {
    // 自定义流处理逻辑
    $this->processChunk($chunk);
}
```

## 安全性

### API 密钥管理

- 使用 Symfony 参数加密安全存储 API 密钥
- 定期轮换 API 密钥
- 为不同环境使用不同的密钥

### 角色权限

- 配置角色特定的 API 密钥访问权限
- 实施基于角色的访问控制
- 定期审计角色使用情况和权限

### 函数调用安全

- 验证所有函数参数
- 实施函数级权限控制
- 记录和监控函数调用以进行安全审计

### 数据保护

- 加密敏感的对话数据
- 实施数据保留策略
- 确保用户数据的 GDPR 合规性

## 依赖关系

此包依赖于以下组件：

- `symfony/framework-bundle`: Symfony 框架核心
- `doctrine/orm`: 数据库对象关系映射
- `easycorp/easyadmin-bundle`: 管理界面
- `symfony/http-client`: HTTP 客户端
- `tourze/*`: 内部扩展包

## 贡献

欢迎提交 Issue 和 Pull Request。

## 许可证

MIT
