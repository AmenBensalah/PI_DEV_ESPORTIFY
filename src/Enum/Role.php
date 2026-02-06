<?php

namespace App\Enum;

enum Role: string
{
    case ADMIN = 'ROLE_ADMIN';
    case JOUEUR = 'ROLE_JOUEUR';
    case MANAGER = 'ROLE_MANAGER';
    case ORGANISATEUR = 'ROLE_ORGANISATEUR';
}
