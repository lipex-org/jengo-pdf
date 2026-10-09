<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;

class TestCase extends CIUnitTestCase
{
    protected function setUp(): void
    {
        $this->resetServices();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        \Jengo\Pdf\Pdf::reset();

        parent::tearDown();
    }
}
