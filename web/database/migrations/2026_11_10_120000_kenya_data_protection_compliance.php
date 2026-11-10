<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personal_data_consent_records')) {
            Schema::create('personal_data_consent_records', function (Blueprint $table) {
                $table->id();
                $table->string('subject_type', 50);
                $table->unsignedBigInteger('subject_id');
                $table->string('subject_email', 255)->nullable();
                $table->string('purpose_key', 80);
                $table->string('lawful_basis', 50);
                $table->string('privacy_notice_version', 30);
                $table->string('terms_version', 30)->nullable();
                $table->tinyInteger('consented')->default(1);
                $table->string('consent_method', 50)->default('checkbox');
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('channel', 50)->nullable();
                $table->dateTime('consented_at');
                $table->dateTime('withdrawn_at')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->index(['subject_type', 'subject_id']);
                $table->index('purpose_key');
                $table->index('consented_at');
            });
        }

        if (! Schema::hasTable('data_processing_register')) {
            Schema::create('data_processing_register', function (Blueprint $table) {
                $table->id();
                $table->string('activity_key', 100)->unique();
                $table->string('name', 300);
                $table->text('description')->nullable();
                $table->json('data_subjects')->nullable();
                $table->json('data_categories')->nullable();
                $table->json('database_tables')->nullable();
                $table->string('lawful_basis', 50);
                $table->text('purposes');
                $table->string('retention', 500)->nullable();
                $table->json('recipients')->nullable();
                $table->tinyInteger('cross_border')->default(0);
                $table->text('security_measures')->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            });
        }

        if (! Schema::hasTable('data_subject_requests')) {
            Schema::create('data_subject_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_number', 50)->unique();
                $table->string('requester_name', 200);
                $table->string('requester_email', 255);
                $table->string('requester_phone', 30)->nullable();
                $table->string('subject_type', 50)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('request_type', 50);
                $table->text('description')->nullable();
                $table->string('status', 50)->default('received');
                $table->dateTime('received_at');
                $table->dateTime('due_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->unsignedBigInteger('handled_by')->nullable();
                $table->text('outcome_notes')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
                $table->foreign('handled_by')->references('id')->on('users')->nullOnDelete();
                $table->index('status');
            });
        }

        if (! Schema::hasTable('data_breach_incidents')) {
            Schema::create('data_breach_incidents', function (Blueprint $table) {
                $table->id();
                $table->string('incident_number', 50)->unique();
                $table->dateTime('discovered_at');
                $table->dateTime('reported_odpc_at')->nullable();
                $table->unsignedInteger('affected_subjects_count')->nullable();
                $table->json('data_categories')->nullable();
                $table->text('description');
                $table->text('containment_actions')->nullable();
                $table->string('status', 50)->default('open');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        foreach (config('data-protection.processing_activities', []) as $activity) {
            $key = $activity['activity_key'] ?? null;
            if (! $key || ! Schema::hasTable('data_processing_register')) {
                continue;
            }

            $exists = DB::table('data_processing_register')->where('activity_key', $key)->exists();
            if ($exists) {
                continue;
            }

            DB::table('data_processing_register')->insert([
                'activity_key' => $key,
                'name' => $activity['name'],
                'description' => $activity['description'] ?? null,
                'data_subjects' => json_encode($activity['data_subjects'] ?? []),
                'data_categories' => json_encode($activity['data_categories'] ?? []),
                'database_tables' => json_encode($activity['database_tables'] ?? []),
                'lawful_basis' => $activity['lawful_basis'] ?? 'contract',
                'purposes' => $activity['purposes'] ?? '',
                'retention' => $activity['retention'] ?? null,
                'recipients' => json_encode($activity['recipients'] ?? []),
                'cross_border' => ! empty($activity['cross_border']) ? 1 : 0,
                'security_measures' => $activity['security_measures'] ?? null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('applicants')) {
            Schema::table('applicants', function (Blueprint $table) {
                if (! Schema::hasColumn('applicants', 'privacy_notice_version')) {
                    $table->string('privacy_notice_version', 30)->nullable()->after('application_source');
                }
                if (! Schema::hasColumn('applicants', 'privacy_consented_at')) {
                    $table->dateTime('privacy_consented_at')->nullable()->after('privacy_notice_version');
                }
            });
        }

        if (Schema::hasTable('cms_pages')) {
            $body = \App\Support\KenyaDataProtectionNotice::htmlBody();
            $version = \App\Support\KenyaDataProtectionNotice::policyVersion();
            DB::table('cms_pages')
                ->where('slug', 'privacy')
                ->update([
                    'body' => $body,
                    'seo_meta_description' => 'Privacy notice under the Kenya Data Protection Act, 2019 for TICH platforms.',
                    'updated_at' => now(),
                ]);

            if (! DB::table('cms_pages')->where('slug', 'privacy')->exists()) {
                DB::table('cms_pages')->insert([
                    'slug' => 'privacy',
                    'title' => 'Privacy Policy',
                    'body' => $body,
                    'status' => 'published',
                    'published_at' => now(),
                    'seo_meta_title' => 'Privacy Policy',
                    'seo_meta_description' => 'Privacy notice under the Kenya Data Protection Act, 2019 for TICH platforms.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('applicants')) {
            Schema::table('applicants', function (Blueprint $table) {
                if (Schema::hasColumn('applicants', 'privacy_consented_at')) {
                    $table->dropColumn('privacy_consented_at');
                }
                if (Schema::hasColumn('applicants', 'privacy_notice_version')) {
                    $table->dropColumn('privacy_notice_version');
                }
            });
        }

        Schema::dropIfExists('data_breach_incidents');
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('data_processing_register');
        Schema::dropIfExists('personal_data_consent_records');
    }
};
