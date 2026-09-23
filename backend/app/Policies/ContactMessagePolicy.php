<?php

namespace App\Policies;

use App\Policies\Concerns\RestrictsDeletionToAdmin;

class ContactMessagePolicy
{
    use RestrictsDeletionToAdmin;
}
