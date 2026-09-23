<?php

namespace App\Policies;

use App\Policies\Concerns\RestrictsDeletionToAdmin;

class DeliverySlotTemplatePolicy
{
    use RestrictsDeletionToAdmin;
}
