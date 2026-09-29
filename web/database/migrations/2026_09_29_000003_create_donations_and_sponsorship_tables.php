<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campaign `donations` table already exists — add public pledge fields.
        if (Schema::hasTable('donations')) {
            Schema::table('donations', function (Blueprint $table) {
                if (! Schema::hasColumn('donations', 'donation_type')) {
                    $table->string('donation_type', 30)->nullable()->after('donation_number');
                }
                if (! Schema::hasColumn('donations', 'designation')) {
                    $table->string('designation', 50)->nullable()->after('amount');
                }
                if (! Schema::hasColumn('donations', 'message')) {
                    $table->text('message')->nullable()->after('donor_phone');
                }
                if (! Schema::hasColumn('donations', 'status')) {
                    $table->string('status', 30)->default('pending')->after('notes');
                }
                if (! Schema::hasColumn('donations', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                if (Schema::hasColumn('donations', 'payment_method')) {
                    DB::statement('ALTER TABLE `donations` MODIFY `payment_method` varchar(50) NULL');
                }
                if (Schema::hasColumn('donations', 'donation_date')) {
                    DB::statement('ALTER TABLE `donations` MODIFY `donation_date` date NULL');
                }
                if (Schema::hasColumn('donations', 'amount_KES')) {
                    DB::statement('ALTER TABLE `donations` MODIFY `amount_KES` decimal(14,2) NULL');
                }
            }
        } else {
            Schema::create('donations', function (Blueprint $table) {
                $table->id();
                $table->string('donation_number', 50)->unique();
                $table->enum('donation_type', ['one_time', 'monthly', 'annual'])->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('designation', 50)->nullable();
                $table->string('donor_name', 300);
                $table->string('donor_email', 255);
                $table->string('donor_phone', 30)->nullable();
                $table->text('message')->nullable();
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index('donor_email');
            });
        }

        if (! Schema::hasTable('sponsorship_inquiries')) {
            Schema::create('sponsorship_inquiries', function (Blueprint $table) {
                $table->id();
                $table->enum('sponsor_type', ['full', 'partial', 'co_sponsor', 'named_scholarship']);
                $table->enum('duration', ['1', '2', '3', 'ongoing']);
                $table->string('preferred_field', 50)->nullable();
                $table->string('sponsor_name', 300);
                $table->string('sponsor_email', 255);
                $table->string('sponsor_phone', 30);
                $table->text('sponsor_message')->nullable();
                $table->enum('status', ['pending', 'contacted', 'in_progress', 'completed', 'declined'])->default('pending');
                $table->timestamps();

                $table->index(['status', 'created_at'], 'sponsorship_inquiries_status_created_idx');
                $table->index('sponsor_email', 'sponsorship_inquiries_email_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorship_inquiries');

        if (Schema::hasTable('donations')) {
            Schema::table('donations', function (Blueprint $table) {
                foreach (['donation_type', 'designation', 'message', 'status', 'updated_at'] as $column) {
                    if (Schema::hasColumn('donations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
