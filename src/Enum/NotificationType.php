<?php

namespace App\Enum;

enum NotificationType: string
{
    case GENERAL = 'general';
    case WELCOME = 'welcome';
    case POST = 'post';
    case TEAM_REPORT = 'team_report';
    case LIKE = 'like';
    case PARTICIPATION = 'participation';
}
