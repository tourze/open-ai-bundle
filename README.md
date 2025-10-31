# OpenAIBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/open-ai-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/open-ai-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/open-ai-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/open-ai-bundle)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg?style=flat-square)](https://www.php.net/)
[![Build Status](https://img.shields.io/github/workflow/status/tourze/php-monorepo/CI?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

A comprehensive Symfony bundle for integrating with DeepSeek API and other AI models, 
providing conversation management, function calling, and easy-to-use chat interfaces.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Advanced Configuration](#advanced-configuration)
- [Usage](#usage)
- [Advanced Usage](#advanced-usage)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)

## Features

- 🤖 **Multiple AI Model Support**: DeepSeek (coder, chat, math, chinese), and customizable models
- 💬 **Conversation Management**: Full conversation history with role-based characters
- 🔧 **Function Calling**: Built-in AI functions for code analysis, file operations, and database queries
- 🌊 **Streaming Responses**: Real-time streaming output with chain-of-thought support
- 🎨 **Admin Interface**: EasyAdmin integration for managing API keys, characters, and conversations
- 💻 **CLI Tools**: Interactive chat command with multiple modes
- 🔒 **Secure**: API key management with character-specific permissions

## Requirements

- PHP 8.1 or higher
- Symfony 6.4 or higher
- Doctrine ORM 3.0 or higher

## Installation

```bash
composer require tourze/open-ai-bundle
```

## Quick Start

### 1. Configure Database

Run migrations to create the required tables:

```bash
php bin/console doctrine:migrations:migrate
```

### 2. Set Up API Keys

Create an API key in the database:

```sql
INSERT INTO open_ai_api_key (id, title, base_url, api_key, model, status) 
VALUES (1, 'DeepSeek API', 'https://api.deepseek.com', 'your-api-key', 'deepseek-chat', 1);
```

### 3. Create a Character

```sql
INSERT INTO open_ai_character (id, name, system_prompt, status, preferred_api_key_id) 
VALUES (1, 'Assistant', 'You are a helpful assistant.', 1, 1);
```

### 4. Start Chatting

```bash
# Interactive mode
php bin/console open-ai:chat -c 1

# Single prompt mode
php bin/console open-ai:chat -c 1 -p "Write a poem about coding"
```

## Usage

### Command Line Interface

The `open-ai:chat` command provides flexible interaction modes:

```bash
# Basic interactive chat
php bin/console open-ai:chat --character 1

# Non-streaming mode
php bin/console open-ai:chat -c 1 --no-stream

# Quiet mode (only AI responses)
php bin/console open-ai:chat -c 1 --prompt "Hello" --quiet

# Debug mode
php bin/console open-ai:chat -c 1 --debug
```

**Interactive Commands:**
- Type `q`, `quit`, or `exit` to exit
- Type `c` or `clear` to clear conversation history

### Programmatic Usage

```php
use OpenAIBundle\Service\OpenAiService;
use OpenAIBundle\Service\ConversationService;
use OpenAIBundle\VO\StreamRequestOptions;

// Inject services
$openAiService = $container->get(OpenAiService::class);
$conversationService = $container->get(ConversationService::class);

// Initialize conversation
$conversation = $conversationService->initConversation($character, $apiKey);

// Add user message
$conversationService->createUserMessage($conversation, $apiKey, "Hello!");

// Get AI response (streaming)
$options = new StreamRequestOptions(
    model: 'deepseek-chat',
    temperature: 0.7,
    maxTokens: 2000
);

foreach ($openAiService->streamReasoner($apiKey, $messages, $options) as $chunk) {
    // Process streaming chunks
    foreach ($chunk->getChoices() as $choice) {
        echo $choice->getContent();
    }
}
```

### Function Calling

The bundle includes several built-in AI functions:

- **Code Analysis**: Analyze code structure, find references
- **File Operations**: List files, read text files
- **Database Operations**: Query tables, fetch results
- **System Information**: Get timezone, random numbers

Enable function calling on an API key:

```sql
UPDATE open_ai_api_key SET function_calling = 1 WHERE id = 1;
```

### Admin Interface

Access the admin panel to manage:
- API Keys: `/admin?crudAction=index&crudControllerFqcn=OpenAIBundle\Controller\Admin\ApiKeyCrudController`
- Characters: `/admin?crudAction=index&crudControllerFqcn=OpenAIBundle\Controller\Admin\CharacterCrudController`
- Conversations: `/admin?crudAction=index&crudControllerFqcn=OpenAIBundle\Controller\Admin\ConversationCrudController`

## Advanced Configuration

### Character Customization

```php
$character = new Character();
$character->setName('Code Expert');
$character->setSystemPrompt('You are an expert programmer...');
$character->setTemperature(0.5);
$character->setMaxTokens(4000);
$character->setTopP(0.9);
$character->setFrequencyPenalty(0.0);
$character->setPresencePenalty(0.0);
```

### Custom AI Functions

Create custom functions by implementing the function interface:

```php
namespace App\AiFunction;

use OpenAIBundle\Service\AiFunctionInterface;
use OpenAIBundle\VO\FunctionDefinition;

class MyCustomFunction implements AiFunctionInterface
{
    public function getDefinition(): FunctionDefinition
    {
        // Define function parameters and description
    }
    
    public function execute(array $args): string
    {
        // Implement function logic
    }
}
```

## Error Handling

The bundle provides specific exception types:

```php
try {
    $result = $openAiService->chat($apiKey, $messages, $options);
} catch (ConfigurationException $e) {
    // Handle configuration errors
} catch (ModelException $e) {
    // Handle model-related errors
} catch (OpenAiException $e) {
    // Handle general errors
}
```

## Advanced Usage

### Custom AI Functions

Create custom AI functions by implementing the `AiFunctionInterface`:

```php
use OpenAIBundle\AiFunction\AiFunctionInterface;

class CustomFunction implements AiFunctionInterface
{
    public function getDefinition(): FunctionDefinition
    {
        return new FunctionDefinition(
            'custom_function',
            'Description of your function',
            [
                new FunctionParam('param_name', FunctionParamType::STRING, 'Parameter description', true)
            ]
        );
    }

    public function execute(array $arguments): string
    {
        // Your custom logic here
        return json_encode(['result' => 'success']);
    }
}
```

### Conversation Callbacks

Implement conversation callbacks for custom processing:

```php
$conversationService->onMessageCreated(function (Message $message) {
    // Custom processing for each message
});
```

### Stream Processing

Handle streaming responses with custom processors:

```php
foreach ($openAiService->streamReasoner($apiKey, $messages, $options) as $chunk) {
    // Custom stream processing
    $this->processChunk($chunk);
}
```

## Security

### API Key Management

- Store API keys securely using Symfony's parameter encryption
- Rotate API keys regularly
- Use environment-specific keys for different environments

### Character Permissions

- Configure character-specific API key access
- Implement role-based access control for characters
- Audit character usage and permissions regularly

### Function Calling Security

- Validate all function parameters
- Implement function-level permissions
- Log and monitor function calls for security auditing

### Data Protection

- Encrypt sensitive conversation data
- Implement data retention policies
- Ensure GDPR compliance for user data

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
