<?php

namespace App\Policies;

use App\Policies\Concerns\RestrictsDeletionToAdmin;

class CategoryPolicy
{
    use RestrictsDeletionToAdmin;
}
