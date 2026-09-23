<?php

namespace App\Policies;

use App\Policies\Concerns\RestrictsDeletionToAdmin;

class SettingPolicy
{
    use RestrictsDeletionToAdmin;
}
