<?php

namespace OpenAIBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

#[AdminCrud(routePath: '/open-ai/message', routeName: 'open_ai_message')]
class MessageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Message::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('AI消息')
            ->setEntityLabelInPlural('AI消息管理')
            ->setPageTitle('index', 'AI消息列表')
            ->setPageTitle('new', '新建AI消息')
            ->setPageTitle('edit', '编辑AI消息')
            ->setPageTitle('detail', 'AI消息详情')
            ->setHelp('index', '管理AI对话中的消息记录，包括用户消息和AI回复')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['content', 'msgId', 'model']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield TextField::new('msgId', '消息ID')
            ->setMaxLength(120)
            ->setRequired(true)
            ->setHelp('唯一的消息标识符');

        yield AssociationField::new('conversation', '所属对话')
            ->setRequired(true)
            ->setHelp('该消息所属的对话会话');

        yield ChoiceField::new('role', '角色')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => RoleEnum::class])
            ->formatValue(function ($value) {
                return $value instanceof RoleEnum ? $value->value : '';
            })
            ->setRequired(true)
            ->setHelp('消息的发送者角色');

        yield TextareaField::new('content', '消息内容')
            ->setNumOfRows(6)
            ->setRequired(true)
            ->setHelp('消息的文本内容');

        yield TextareaField::new('reasoningContent', '推理过程')
            ->setNumOfRows(4)
            ->setHelp('AI的思考推理过程');

        yield TextareaField::new('toolCalls', '工具调用')
            ->setNumOfRows(3)
            ->formatValue(function ($value) {
                return $value ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';
            })
            ->setHelp('调用的工具函数信息');

        yield TextField::new('toolCallId', '工具调用ID')
            ->setMaxLength(50)
            ->setHelp('工具调用的唯一标识符');

        yield TextField::new('model', '使用模型')
            ->setMaxLength(50)
            ->setRequired(true)
            ->setHelp('生成该消息使用的AI模型');

        yield IntegerField::new('promptTokens', '输入令牌数')
            ->setHelp('提示词消耗的令牌数量');

        yield IntegerField::new('completionTokens', '输出令牌数')
            ->setHelp('生成内容消耗的令牌数量');

        yield IntegerField::new('totalTokens', '总令牌数')
            ->setHelp('该消息消耗的总令牌数量');

        yield AssociationField::new('apiKey', '使用密钥')
            ->setHelp('生成该消息使用的API密钥');

        yield TextField::new('createdBy', '创建人')
            ->hideOnForm();

        yield TextField::new('updatedBy', '更新人')
            ->hideOnForm();

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
        $choices = [];
        foreach (RoleEnum::cases() as $case) {
            $choices[$case->value] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('msgId', '消息ID'))
            ->add(EntityFilter::new('conversation', '所属对话'))
            ->add(ChoiceFilter::new('role', '角色')->setChoices($choices))
            ->add(TextFilter::new('model', '使用模型'))
            ->add(EntityFilter::new('apiKey', '使用密钥'))
            ->add(NumericFilter::new('totalTokens', '总令牌数'))
            ->add(TextFilter::new('createdBy', '创建人'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'));
    }
} 