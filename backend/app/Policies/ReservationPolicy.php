<?php

namespace App\Policies;

use App\Policies\Concerns\RestrictsDeletionToAdmin;

class ReservationPolicy
{
    use RestrictsDeletionToAdmin;
}
