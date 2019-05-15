<?php

namespace Tests\Unit;

use App\Services\NormalizedStringService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NormalizedStringTest extends TestCase
{
    /** @var NormalizedStringService */
    private $service;

    public function setUp()
    {
        parent::setUp();

        $this->service = new NormalizedStringService();
    }

    public function test_it_takes_only_correct_symbols()
    {
        $stringWithIncorrectSymbols = '[(){} fsaddfs]';

        $this->expectException(\Exception::class);

        $this->service->isStringNormalized($stringWithIncorrectSymbols);
    }

    public function test_it_fails_for_not_normalized_string()
    {
        $string = '{[}]';

        $this->assertFalse($this->service->isStringNormalized($string));
    }

    public function test_it_checks_that_string_is_normalized()
    {
        $string = '[([]{[]})]';

        $this->assertTrue($this->service->isStringNormalized($string));
    }
}
