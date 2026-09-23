<?php

namespace App\Policies;

use App\Policies\Concerns\RestrictsDeletionToAdmin;

class CustomerPolicy
{
    use RestrictsDeletionToAdmin;
}
