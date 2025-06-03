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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Enum\ContextLength;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

#[AdminCrud(routePath: '/open-ai/api-key', routeName: 'open_ai_api_key')]
class ApiKeyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ApiKey::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('API密钥')
            ->setEntityLabelInPlural('API密钥管理')
            ->setPageTitle('index', 'API密钥列表')
            ->setPageTitle('new', '新建API密钥')
            ->setPageTitle('edit', '编辑API密钥')
            ->setPageTitle('detail', 'API密钥详情')
            ->setHelp('index', '管理OpenAI API密钥配置，包括模型、接口地址等设置')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['title', 'model', 'apiKey']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield BooleanField::new('valid', '有效状态')
            ->setFormTypeOption('attr', ['checked' => 'checked']);

        yield TextField::new('title', '密钥标题')
            ->setMaxLength(100)
            ->setRequired(true);

        yield TextField::new('apiKey', 'API密钥')
            ->setMaxLength(255)
            ->setRequired(true)
            ->setHelp('请输入从OpenAI获取的API密钥');

        yield TextField::new('model', '调用模型')
            ->setMaxLength(120)
            ->setRequired(true)
            ->setHelp('如：gpt-3.5-turbo, gpt-4, deepseek-chat等');

        yield TextField::new('chatCompletionUrl', '聊天补全接口URL')
            ->setMaxLength(255)
            ->setRequired(true)
            ->setHelp('OpenAI兼容的聊天补全API接口地址');

        yield BooleanField::new('functionCalling', '支持函数调用')
            ->setHelp('该模型是否支持函数调用功能');

        yield ChoiceField::new('contextLength', '上下文长度')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => ContextLength::class])
            ->formatValue(function ($value) {
                return $value instanceof ContextLength ? $value->getLabel() : '';
            })
            ->setHelp('模型支持的最大上下文长度');

        yield AssociationField::new('messages', '相关消息')
            ->hideOnForm()
            ->onlyOnDetail();

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', '密钥标题'))
            ->add(TextFilter::new('model', '模型'))
            ->add(BooleanFilter::new('valid', '有效状态'))
            ->add(BooleanFilter::new('functionCalling', '支持函数调用'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'));
    }
} 