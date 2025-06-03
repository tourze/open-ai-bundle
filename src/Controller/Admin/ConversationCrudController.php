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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use OpenAIBundle\Entity\Conversation;

#[AdminCrud(routePath: '/open-ai/conversation', routeName: 'open_ai_conversation')]
class ConversationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Conversation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('AI对话')
            ->setEntityLabelInPlural('AI对话管理')
            ->setPageTitle('index', 'AI对话列表')
            ->setPageTitle('new', '新建AI对话')
            ->setPageTitle('edit', '编辑AI对话')
            ->setPageTitle('detail', 'AI对话详情')
            ->setHelp('index', '管理AI对话会话，查看对话历史和统计信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['title', 'description', 'model']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield BooleanField::new('valid', '有效状态')
            ->setFormTypeOption('attr', ['checked' => 'checked']);

        yield TextField::new('title', '对话标题')
            ->setMaxLength(255)
            ->setRequired(true);

        yield TextareaField::new('description', '对话描述')
            ->setNumOfRows(3)
            ->setHelp('对这次对话的简短描述');

        yield TextField::new('model', '使用模型')
            ->setMaxLength(50)
            ->setRequired(true)
            ->setHelp('该对话使用的AI模型');

        yield TextareaField::new('systemPrompt', '系统提示词')
            ->setNumOfRows(5)
            ->setHelp('该对话的系统提示词设置');

        yield AssociationField::new('actor', '对话角色')
            ->setRequired(true)
            ->setHelp('参与对话的AI角色');

        yield TextField::new('createdBy', '创建人')
            ->hideOnForm();

        yield TextField::new('updatedBy', '更新人')
            ->hideOnForm();

        yield AssociationField::new('messages', '消息列表')
            ->hideOnForm()
            ->onlyOnDetail()
            ->setHelp('该对话的所有消息记录');

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
            ->add(TextFilter::new('title', '对话标题'))
            ->add(TextFilter::new('model', '使用模型'))
            ->add(BooleanFilter::new('valid', '有效状态'))
            ->add(EntityFilter::new('actor', '对话角色'))
            ->add(TextFilter::new('createdBy', '创建人'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'));
    }
} 