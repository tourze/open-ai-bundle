<?php

namespace OpenAIBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use OpenAIBundle\Service\AdminMenu;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 设置 LinkGenerator 的 mock
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator->method('getCurdListPage')->willReturn('/admin/list/entity');

        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);
    }

    public function testMenuProviderInterface(): void
    {
        $menuItem = $this->createMock(ItemInterface::class);
        $openAiMenuItem = $this->createMock(ItemInterface::class);

        // 配置主菜单项的行为
        $menuItem->expects($this->once())
            ->method('addChild')
            ->with('OpenAI', ['label' => 'OpenAI', 'icon' => 'fas fa-robot'])
            ->willReturn($openAiMenuItem)
        ;

        // 配置子菜单项的行为
        $childMenuItem = $this->createMock(ItemInterface::class);

        $openAiMenuItem->expects($this->exactly(4))
            ->method('addChild')
            ->willReturn($childMenuItem)
        ;

        // 从容器获取 AdminMenu 服务并测试
        $adminMenu = self::getService(AdminMenu::class);
        ($adminMenu)($menuItem);

        // 验证AdminMenu服务被正确实例化
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }
}
