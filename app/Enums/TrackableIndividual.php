<?php

namespace App\Enums;

enum TrackablePerson: string
{
    case USER = 'user';
    case VISITOR = 'visitor';
}
