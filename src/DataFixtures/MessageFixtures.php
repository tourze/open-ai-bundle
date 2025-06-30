<?php

namespace OpenAIBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;

/**
 * AI消息数据装置 - 用于生成测试和开发用的消息数据
 */
class MessageFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 为通用聊天对话创建消息
        $this->createGeneralChatMessages($manager);
        
        // 为翻译任务对话创建消息
        $this->createTranslationMessages($manager);
        
        // 为编程帮助对话创建消息
        $this->createProgrammingMessages($manager);
        
        // 为学习辅导对话创建消息
        $this->createLearningMessages($manager);

        $manager->flush();
    }

    private function createGeneralChatMessages(ObjectManager $manager): void
    {
        $conversation = $this->getReference(ConversationFixtures::GENERAL_CHAT_REFERENCE, Conversation::class);
        $apiKey = $this->getReference(ApiKeyFixtures::GPT_35_TURBO_KEY_REFERENCE, ApiKey::class);

        // 用户问题
        $userMessage1 = new Message();
        $userMessage1->setMsgId('msg_user_' . uniqid());
        $userMessage1->setConversation($conversation);
        $userMessage1->setRole(RoleEnum::user);
        $userMessage1->setContent('你好！今天天气怎么样？');
        $userMessage1->setModel('gpt-3.5-turbo');
        $userMessage1->setPromptTokens(15);
        $userMessage1->setCompletionTokens(0);
        $userMessage1->setTotalTokens(15);
        $userMessage1->setApiKey($apiKey);

        $manager->persist($userMessage1);

        // AI回复
        $assistantMessage1 = new Message();
        $assistantMessage1->setMsgId('msg_assistant_' . uniqid());
        $assistantMessage1->setConversation($conversation);
        $assistantMessage1->setRole(RoleEnum::assistant);
        $assistantMessage1->setContent('你好！很抱歉，我无法直接获取实时天气信息。不过我可以为你介绍一些查看天气的方法，比如使用天气应用、查看天气网站，或者询问语音助手。你想了解哪个地区的天气呢？');
        $assistantMessage1->setModel('gpt-3.5-turbo');
        $assistantMessage1->setPromptTokens(15);
        $assistantMessage1->setCompletionTokens(72);
        $assistantMessage1->setTotalTokens(87);
        $assistantMessage1->setApiKey($apiKey);

        $manager->persist($assistantMessage1);

        // 继续对话
        $userMessage2 = new Message();
        $userMessage2->setMsgId('msg_user_' . uniqid());
        $userMessage2->setConversation($conversation);
        $userMessage2->setRole(RoleEnum::user);
        $userMessage2->setContent('北京的天气，你能推荐一些好用的天气应用吗？');
        $userMessage2->setModel('gpt-3.5-turbo');
        $userMessage2->setPromptTokens(25);
        $userMessage2->setCompletionTokens(0);
        $userMessage2->setTotalTokens(25);
        $userMessage2->setApiKey($apiKey);

        $manager->persist($userMessage2);

        $assistantMessage2 = new Message();
        $assistantMessage2->setMsgId('msg_assistant_' . uniqid());
        $assistantMessage2->setConversation($conversation);
        $assistantMessage2->setRole(RoleEnum::assistant);
        $assistantMessage2->setContent('对于北京天气，我推荐以下几个好用的天气应用：\n\n1. **中国天气通** - 中国气象局官方应用，数据权威准确\n2. **墨迹天气** - 界面美观，功能丰富，包含生活指数\n3. **彩云天气** - 精准的分钟级降雨预报\n4. **Weather** - 苹果自带天气应用，简洁实用\n5. **AccuWeather** - 国际知名气象服务\n\n这些应用都能提供北京地区详细的天气预报信息。你比较倾向于哪种类型的天气应用呢？');
        $assistantMessage2->setModel('gpt-3.5-turbo');
        $assistantMessage2->setPromptTokens(112);
        $assistantMessage2->setCompletionTokens(156);
        $assistantMessage2->setTotalTokens(268);
        $assistantMessage2->setApiKey($apiKey);

        $manager->persist($assistantMessage2);
    }

    private function createTranslationMessages(ObjectManager $manager): void
    {
        $conversation = $this->getReference(ConversationFixtures::TRANSLATION_TASK_REFERENCE, Conversation::class);
        $apiKey = $this->getReference(ApiKeyFixtures::GPT_4_KEY_REFERENCE, ApiKey::class);

        // 翻译请求
        $userMessage = new Message();
        $userMessage->setMsgId('msg_translate_user_' . uniqid());
        $userMessage->setConversation($conversation);
        $userMessage->setRole(RoleEnum::user);
        $userMessage->setContent('请将以下句子翻译成英文：人工智能正在改变我们的生活方式，为各行各业带来了前所未有的机遇和挑战。');
        $userMessage->setModel('gpt-4');
        $userMessage->setPromptTokens(45);
        $userMessage->setCompletionTokens(0);
        $userMessage->setTotalTokens(45);
        $userMessage->setApiKey($apiKey);

        $manager->persist($userMessage);

        // 翻译结果
        $assistantMessage = new Message();
        $assistantMessage->setMsgId('msg_translate_assistant_' . uniqid());
        $assistantMessage->setConversation($conversation);
        $assistantMessage->setRole(RoleEnum::assistant);
        $assistantMessage->setContent('Artificial intelligence is transforming our way of life, bringing unprecedented opportunities and challenges to all industries.');
        $assistantMessage->setModel('gpt-4');
        $assistantMessage->setPromptTokens(45);
        $assistantMessage->setCompletionTokens(18);
        $assistantMessage->setTotalTokens(63);
        $assistantMessage->setApiKey($apiKey);

        $manager->persist($assistantMessage);
    }

    private function createProgrammingMessages(ObjectManager $manager): void
    {
        $conversation = $this->getReference(ConversationFixtures::PROGRAMMING_HELP_REFERENCE, Conversation::class);
        $apiKey = $this->getReference(ApiKeyFixtures::GPT_4_KEY_REFERENCE, ApiKey::class);

        // 编程问题
        $userMessage = new Message();
        $userMessage->setMsgId('msg_code_user_' . uniqid());
        $userMessage->setConversation($conversation);
        $userMessage->setRole(RoleEnum::user);
        $userMessage->setContent('在PHP中，如何优雅地处理数据库连接异常？请提供一个实际的代码示例。');
        $userMessage->setModel('gpt-4');
        $userMessage->setPromptTokens(35);
        $userMessage->setCompletionTokens(0);
        $userMessage->setTotalTokens(35);
        $userMessage->setApiKey($apiKey);

        $manager->persist($userMessage);

        // 编程解答
        $assistantMessage = new Message();
        $assistantMessage->setMsgId('msg_code_assistant_' . uniqid());
        $assistantMessage->setConversation($conversation);
        $assistantMessage->setRole(RoleEnum::assistant);
        $assistantMessage->setContent('在PHP中处理数据库连接异常，建议使用try-catch块和自定义异常处理。以下是一个使用PDO的实际示例：

```php
<?php

class DatabaseConnection
{
    private $pdo;
    private $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }
    
    private function connect(): void
    {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=utf8mb4",
            $this->config[\'host\'],
            $this->config[\'dbname\']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config[\'username\'],
                $this->config[\'password\'],
                $options
            );
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }
    
    private function handleConnectionError(PDOException $e): void
    {
        // 记录错误日志
        error_log("Database connection failed: " . $e->getMessage());
        
        // 根据错误类型采取不同处理策略
        if (str_contains($e->getMessage(), \'Access denied\')) {
            throw new InvalidArgumentException(\'数据库认证失败，请检查用户名和密码\');
        } elseif (str_contains($e->getMessage(), \'Unknown database\')) {
            throw new InvalidArgumentException(\'指定的数据库不存在\');
        } else {
            throw new RuntimeException(\'数据库连接失败，请稍后重试\');
        }
    }
    
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}

// 使用示例
try {
    $config = [
        \'host\' => \'localhost\',
        \'dbname\' => \'testdb\',
        \'username\' => \'user\',
        \'password\' => \'password\'
    ];
    
    $db = new DatabaseConnection($config);
    $pdo = $db->getPdo();
    
    // 执行数据库操作
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([1]);
    $user = $stmt->fetch();
    
} catch (InvalidArgumentException $e) {
    // 处理配置错误
    echo "配置错误: " . $e->getMessage();
} catch (RuntimeException $e) {
    // 处理连接错误
    echo "连接错误: " . $e->getMessage();
} catch (Exception $e) {
    // 处理其他未预期的错误
    echo "未知错误: " . $e->getMessage();
}
```

关键要点：
1. 使用具体的异常类型来区分不同的错误情况
2. 记录详细的错误日志用于调试
3. 向用户返回友好的错误信息
4. 避免暴露敏感的系统信息');
        $assistantMessage->setModel('gpt-4');
        $assistantMessage->setPromptTokens(35);
        $assistantMessage->setCompletionTokens(620);
        $assistantMessage->setTotalTokens(655);
        $assistantMessage->setApiKey($apiKey);

        $manager->persist($assistantMessage);
    }

    private function createLearningMessages(ObjectManager $manager): void
    {
        $conversation = $this->getReference(ConversationFixtures::LEARNING_SESSION_REFERENCE, Conversation::class);
        $apiKey = $this->getReference(ApiKeyFixtures::CLAUDE_KEY_REFERENCE, ApiKey::class);

        // 学习提问
        $userMessage = new Message();
        $userMessage->setMsgId('msg_learn_user_' . uniqid());
        $userMessage->setConversation($conversation);
        $userMessage->setRole(RoleEnum::user);
        $userMessage->setContent('什么是机器学习？它与传统编程有什么区别？');
        $userMessage->setModel('claude-3-sonnet-20240229');
        $userMessage->setPromptTokens(20);
        $userMessage->setCompletionTokens(0);
        $userMessage->setTotalTokens(20);
        $userMessage->setApiKey($apiKey);

        $manager->persist($userMessage);

        // 教学回答
        $assistantMessage = new Message();
        $assistantMessage->setMsgId('msg_learn_assistant_' . uniqid());
        $assistantMessage->setConversation($conversation);
        $assistantMessage->setRole(RoleEnum::assistant);
        $assistantMessage->setContent('机器学习是一门让计算机能够从数据中自动学习和改进的技术，无需明确编程指令。

让我用一个简单的比喻来解释：

**传统编程** 就像是给计算机写一本详细的说明书：
- 程序员告诉计算机每一步该怎么做
- 例如：如果温度>30度，则显示"天气很热"
- 所有规则都由人类预先定义

**机器学习** 就像是教一个孩子识别事物：
- 给计算机大量的例子（数据）
- 让它自己找出规律和模式
- 例如：给它看1000张猫的照片和1000张狗的照片，它学会区分猫和狗

## 主要区别：

1. **方法不同**
   - 传统编程：规则 + 数据 → 结果
   - 机器学习：数据 + 结果 → 规则

2. **处理复杂性**
   - 传统编程：适合逻辑清晰的问题
   - 机器学习：适合复杂模式识别问题

3. **适应性**
   - 传统编程：规则固定，需要人工修改
   - 机器学习：能随着新数据不断改进

想象一下邮件垃圾过滤：传统方法需要程序员写出所有判断垃圾邮件的规则，而机器学习只需要给它大量标记好的邮件例子，它就能自己学会识别。

你还想了解机器学习的哪个方面呢？');
        $assistantMessage->setModel('claude-3-sonnet-20240229');
        $assistantMessage->setPromptTokens(20);
        $assistantMessage->setCompletionTokens(380);
        $assistantMessage->setTotalTokens(400);
        $assistantMessage->setApiKey($apiKey);

        $manager->persist($assistantMessage);
    }

    public function getDependencies(): array
    {
        return [
            ConversationFixtures::class,
            ApiKeyFixtures::class,
        ];
    }
}