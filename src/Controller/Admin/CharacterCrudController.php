<?php

namespace OpenAIBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use OpenAIBundle\Entity\Character;

/**
 * @extends AbstractCrudController<Character>
 */
#[AdminCrud(routePath: '/open-ai/character', routeName: 'open_ai_character')]
final class CharacterCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Character::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('AI角色')
            ->setEntityLabelInPlural('AI角色管理')
            ->setPageTitle('index', 'AI角色列表')
            ->setPageTitle('new', '新建AI角色')
            ->setPageTitle('edit', '编辑AI角色')
            ->setPageTitle('detail', 'AI角色详情')
            ->setHelp('index', '管理AI对话角色配置，包括角色设定、参数调优等')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['name', 'description'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('name', '角色名称')
            ->setMaxLength(50)
            ->setRequired(true)
        ;

        yield ImageField::new('avatar', '头像')
            ->setBasePath('/uploads/avatars')
            ->setUploadDir('public/uploads/avatars')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setHelp('上传角色头像图片')
        ;

        yield TextareaField::new('description', '描述')
            ->setNumOfRows(3)
            ->setHelp('角色的简短描述')
        ;

        yield TextareaField::new('systemPrompt', '系统提示词')
            ->setNumOfRows(8)
            ->setRequired(true)
            ->setHelp('定义角色行为和回答风格的系统提示词')
        ;

        yield NumberField::new('temperature', '温度参数')
            ->setNumDecimals(1)
            ->setHelp('控制输出随机性，范围0-1，值越高越随机')
        ;

        yield NumberField::new('topP', '采样概率阈值')
            ->setNumDecimals(2)
            ->setHelp('核采样概率阈值，范围0-1')
        ;

        yield NumberField::new('maxTokens', '最大生成令牌数')
            ->setHelp('单次回复的最大令牌数量')
        ;

        yield NumberField::new('presencePenalty', '存在惩罚')
            ->setNumDecimals(1)
            ->setHelp('避免重复主题，范围-2到2')
        ;

        yield NumberField::new('frequencyPenalty', '频率惩罚')
            ->setNumDecimals(1)
            ->setHelp('避免重复用词，范围-2到2')
        ;

        yield AssociationField::new('preferredApiKey', '偏好API密钥')
            ->setHelp('该角色默认使用的API密钥')
        ;

        yield CollectionField::new('supportFunctions', '支持函数')
            ->allowAdd()
            ->allowDelete()
            ->setHelp('该角色可以调用的AI函数列表')
            ->hideOnIndex()
        ;

        yield BooleanField::new('valid', '有效状态')
            ->setFormTypeOption('attr', ['checked' => 'checked'])
        ;

        yield AssociationField::new('conversations', '相关对话')
            ->hideOnForm()
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '角色名称'))
            ->add(BooleanFilter::new('valid', '有效状态'))
            ->add(EntityFilter::new('preferredApiKey', '偏好API密钥'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}
