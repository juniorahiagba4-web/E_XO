<?php

namespace App\Policies;

use App\Policies\Concerns\RestrictsDeletionToAdmin;

class ItemPolicy
{
    use RestrictsDeletionToAdmin;
}
