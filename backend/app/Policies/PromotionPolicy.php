<?php

namespace App\Policies;

use App\Policies\Concerns\RestrictsDeletionToAdmin;

class PromotionPolicy
{
    use RestrictsDeletionToAdmin;
}
