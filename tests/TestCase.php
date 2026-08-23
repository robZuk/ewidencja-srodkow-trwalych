<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render Blade views with @vite; skip the manifest lookup
        // so the suite doesn't depend on a built public/build (absent in CI).
        $this->withoutVite();
    }
}
