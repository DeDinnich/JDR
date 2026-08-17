<?php

namespace App\Enums;

enum UserRole: string
{
    case GameMaster = 'game_master';
    case Player = 'player';
}
