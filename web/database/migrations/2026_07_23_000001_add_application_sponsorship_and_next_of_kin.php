<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('sponsorship_type', 50)->nullable()->after('entry_qualification');
            $table->string('sponsor_organization', 200)->nullable()->after('sponsorship_type');
            $table->string('sponsor_address', 500)->nullable()->after('sponsor_organization');
            $table->string('sponsor_phone', 30)->nullable()->after('sponsor_address');
            $table->string('next_of_kin_name', 300)->nullable()->after('home_county');
            $table->string('next_of_kin_relationship', 50)->nullable()->after('next_of_kin_name');
            $table->string('next_of_kin_phone', 30)->nullable()->after('next_of_kin_relationship');
            $table->string('next_of_kin_address', 500)->nullable()->after('next_of_kin_phone');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn([
                'sponsorship_type',
                'sponsor_organization',
                'sponsor_address',
                'sponsor_phone',
                'next_of_kin_name',
                'next_of_kin_relationship',
                'next_of_kin_phone',
                'next_of_kin_address',
            ]);
        });
    }
};