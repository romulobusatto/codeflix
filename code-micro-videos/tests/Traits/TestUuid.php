<?php

namespace Tests\Traits;

use Ramsey\Uuid\Uuid as RamseyUuid;

trait TestUuid
{
    public function assertIsUuid($condition)
    {
        $this->assertTrue(RamseyUuid::isValid($condition));
    }
}
