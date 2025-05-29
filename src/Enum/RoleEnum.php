<?php

namespace OpenAIBundle\Enum;

enum RoleEnum: string
{
    case system = 'system';
    case user = 'user';
    case assistant = 'assistant';
    case tool = 'tool';
}
