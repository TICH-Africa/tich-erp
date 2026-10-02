<?php

namespace Tests\Unit\Finance;

use App\Services\Finance\ChartOfAccountService;
use Tests\TestCase;

class ChartOfAccountHierarchyTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $chart = [
        '1000', '1010', '1020',
        '1100', '1100.01', '1100.01.02',
        '2000', '2100', '3000',
        '4000', '4010', '5000',
        '61000', '612000', '613000',
    ];

    public function test_it_files_a_code_under_the_parent_plus_separator(): void
    {
        $this->assertSame('1100', $this->resolve('1100.01'));
        $this->assertSame('1100.01', $this->resolve('1100.01.02'));
        $this->assertSame('1100', $this->resolve('1100-01'));
        $this->assertSame('1100', $this->resolve('1100/01'));
        $this->assertSame('1100', $this->resolve('1100_01'));
    }

    public function test_it_files_a_longer_code_under_the_code_it_starts_with(): void
    {
        $this->assertSame('1100', $this->resolve('11000'));
        $this->assertSame('1100', $this->resolve('1100A'));
    }

    public function test_it_files_block_children_under_the_summary_account(): void
    {
        $this->assertSame('61000', $this->resolve('612000'));
        $this->assertSame('61000', $this->resolve('613000'));
        $this->assertSame('61000', $this->resolve('614000'));
    }

    public function test_it_leaves_unrelated_codes_at_the_top_level(): void
    {
        $this->assertNull($this->resolve('62000'));
        $this->assertNull($this->resolve('4010'));
        $this->assertNull($this->resolve('7000'));
    }

    public function test_an_account_is_never_its_own_parent(): void
    {
        $this->assertNull($this->resolve('61000'));
        $this->assertNull($this->resolve('1100'));
    }

    public function test_it_reports_the_rule_that_matched(): void
    {
        $service = app(ChartOfAccountService::class);

        $this->assertSame('separator', $service->resolveParentCode('1100.01', $this->chart)['rule']);
        $this->assertSame('prefix', $service->resolveParentCode('11000', $this->chart)['rule']);
        $this->assertSame('block', $service->resolveParentCode('612000', $this->chart)['rule']);
        $this->assertSame('none', $service->resolveParentCode('7000', $this->chart)['rule']);
    }

    public function test_a_block_child_can_never_hang_off_a_sibling_account(): void
    {
        // 612000 shares its leading digits with 61000 only, so 4000 cannot take it.
        $this->assertNotSame('4000', $this->resolve('612000'));
        $this->assertNotSame('613000', $this->resolve('612000'));
    }

    private function resolve(string $code): ?string
    {
        return app(ChartOfAccountService::class)->parentCodeFor($code, $this->chart);
    }
}
