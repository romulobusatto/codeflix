<?php

namespace Tests;

use Ramsey\Uuid\Uuid as RamseyUuid;

trait Uuid
{
    public function assertIsUuid($condition)
    {
        $this->assertTrue(RamseyUuid::isValid($condition));
    }
}
