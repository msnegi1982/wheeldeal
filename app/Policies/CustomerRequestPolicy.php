<?php

namespace App\Policies;

class CustomerRequestPolicy extends BasePolicy
{
    protected string $resource = 'requests';
}
