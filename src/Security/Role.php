<?php

namespace App\Security;

enum Role: string
{
    case ADMIN = 'ROLE_ADMIN';
    case USER = 'ROLE_USER';
}
