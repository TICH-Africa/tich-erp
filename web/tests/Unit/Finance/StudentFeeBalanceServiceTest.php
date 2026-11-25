<?php

namespace Tests\Unit\Finance;

use App\Models\Student;
use App\Services\Finance\StudentFeeBalanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentFeeBalanceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);
        DB::purge('sqlite');

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->string('status');
            $table->unsignedBigInteger('student_account_id')->nullable();
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->decimal('amount', 10, 2);
            $table->string('status');
            $table->unsignedBigInteger('student_account_id')->nullable();
        });
        Schema::create('student_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->decimal('scholarship_amount', 10, 2)->default(0);
            $table->decimal('helb_amount', 10, 2)->default(0);
            $table->decimal('sponsor_amount', 10, 2)->default(0);
            $table->decimal('work_study_credit', 10, 2)->default(0);
        });
        Schema::create('financial_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('status');
            $table->decimal('amount', 10, 2);
            $table->unsignedBigInteger('student_account_id')->nullable();
        });
    }

    public function test_portal_summary_tracks_partial_and_fully_paid_invoices(): void
    {
        DB::table('invoices')->insert([
            'student_id' => 17,
            'amount' => 1000,
            'balance' => 500,
            'status' => 'partial',
        ]);
        DB::table('payments')->insert([
            'student_id' => 17,
            'amount' => 500,
            'status' => 'SUCCESS',
        ]);

        $student = new Student;
        $student->id = 17;
        $student->fee_clearance_status = 'pending';

        $service = new StudentFeeBalanceService;
        $this->assertSame([
            'outstanding_balance' => 500.0,
            'total_paid' => 500.0,
            'total_chargeable' => 1000.0,
            'fee_clearance_status' => 'pending',
        ], $service->portalSummary($student));

        DB::table('payments')->insert([
            'student_id' => 17,
            'amount' => 500,
            'status' => 'SUCCESS',
        ]);
        DB::table('invoices')->update([
            'balance' => 0,
            'status' => 'paid',
        ]);

        $this->assertSame([
            'outstanding_balance' => 0.0,
            'total_paid' => 1000.0,
            'total_chargeable' => 1000.0,
            'fee_clearance_status' => 'cleared',
        ], $service->portalSummary($student));
    }
}
