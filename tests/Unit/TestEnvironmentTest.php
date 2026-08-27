<?php

namespace Tests\Unit;

use Tests\TestCase;

class TestEnvironmentTest extends TestCase
{
    public function test_application_boots_in_the_testing_environment(): void
    {
        $this->assertTrue(app()->runningUnitTests());
        $this->assertSame('testing', app()->environment());
    }
}
