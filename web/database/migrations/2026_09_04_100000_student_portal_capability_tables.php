<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('applicants')) {
            Schema::table('applicants', function (Blueprint $table) {
                if (! Schema::hasColumn('applicants', 'nationality')) {
                    $table->string('nationality', 100)->nullable()->after('gender');
                }
                if (! Schema::hasColumn('applicants', 'postal_address')) {
                    $table->string('postal_address', 255)->nullable()->after('home_county');
                }
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('password_reset_attempts')) {
            Schema::create('password_reset_attempts', function (Blueprint $table) {
                $table->id();
                $table->string('email', 191)->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->string('status', 30)->default('sent'); // sent|blocked|escalated|completed|ict_reset
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('password_reset_escalations')) {
            Schema::create('password_reset_escalations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('email', 191)->index();
                $table->string('status', 30)->default('open'); // open|resolved|cancelled
                $table->unsignedSmallInteger('attempt_count')->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('resolved_by_user_id')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('resolved_by_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('student_profile_change_requests')) {
            Schema::create('student_profile_change_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('requested_by_user_id');
                $table->string('request_type', 50)->default('profile_update'); // profile_update|photo
                $table->string('status', 30)->default('pending');
                $table->json('current_snapshot')->nullable();
                $table->json('proposed_changes');
                $table->string('attachment_path', 500)->nullable();
                $table->text('student_notes')->nullable();
                $table->text('reviewer_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('requested_by_user_id')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('reviewed_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['student_id', 'status']);
                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('student_transcript_requests')) {
            Schema::create('student_transcript_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('requested_by_user_id');
                $table->string('status', 30)->default('pending'); // pending|processing|issued|rejected|cancelled
                $table->string('delivery_method', 30)->default('download'); // download|email|collect
                $table->text('student_notes')->nullable();
                $table->text('registrar_notes')->nullable();
                $table->string('issued_document_path', 500)->nullable();
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('requested_by_user_id')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('reviewed_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('student_lifecycle_requests')) {
            Schema::create('student_lifecycle_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('requested_by_user_id');
                $table->string('request_type', 50); // deferment|withdrawal|readmission
                $table->string('status', 30)->default('pending');
                $table->date('effective_date')->nullable();
                $table->text('reason')->nullable();
                $table->text('reviewer_notes')->nullable();
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('requested_by_user_id')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('reviewed_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['student_id', 'status']);
            });
        }

        if (! Schema::hasTable('course_evaluation_windows')) {
            Schema::create('course_evaluation_windows', function (Blueprint $table) {
                $table->id();
                $table->string('title', 191);
                $table->unsignedBigInteger('semester_id')->nullable()->index();
                $table->dateTime('opens_at');
                $table->dateTime('closes_at');
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamps();

                $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('course_evaluations')) {
            Schema::create('course_evaluations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('window_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('unit_id')->nullable();
                $table->unsignedBigInteger('staff_id')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->json('responses')->nullable();
                $table->text('comments')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->foreign('window_id')->references('id')->on('course_evaluation_windows')->cascadeOnDelete();
                $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
                $table->unique(['window_id', 'student_id', 'unit_id'], 'course_eval_unique');
            });
        }

        if (! Schema::hasTable('student_document_requests')) {
            Schema::create('student_document_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('requested_by_user_id');
                $table->string('document_type', 80); // transcript|recommendation_letter|completion_letter|clearance_form|other
                $table->string('status', 30)->default('pending');
                $table->text('student_notes')->nullable();
                $table->text('reviewer_notes')->nullable();
                $table->string('issued_document_path', 500)->nullable();
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('requested_by_user_id')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('reviewed_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('student_clearance_items')) {
            Schema::create('student_clearance_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->string('department_key', 50); // finance|library|hostels|academics|registrar
                $table->string('label', 120);
                $table->string('status', 30)->default('pending'); // pending|cleared|blocked|not_required
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('cleared_by_user_id')->nullable();
                $table->timestamp('cleared_at')->nullable();
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('cleared_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->unique(['student_id', 'department_key']);
            });
        }

        if (! Schema::hasTable('student_notifications')) {
            Schema::create('student_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->string('category', 50)->default('general'); // fee|exam|timetable|assignment|academic|general
                $table->string('title', 191);
                $table->text('body')->nullable();
                $table->string('action_url', 500)->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
                $table->index(['student_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notifications');
        Schema::dropIfExists('student_clearance_items');
        Schema::dropIfExists('student_document_requests');
        Schema::dropIfExists('course_evaluations');
        Schema::dropIfExists('course_evaluation_windows');
        Schema::dropIfExists('student_lifecycle_requests');
        Schema::dropIfExists('student_transcript_requests');
        Schema::dropIfExists('student_profile_change_requests');
        Schema::dropIfExists('password_reset_escalations');
        Schema::dropIfExists('password_reset_attempts');

        if (Schema::hasTable('applicants')) {
            Schema::table('applicants', function (Blueprint $table) {
                if (Schema::hasColumn('applicants', 'postal_address')) {
                    $table->dropColumn('postal_address');
                }
                if (Schema::hasColumn('applicants', 'nationality')) {
                    $table->dropColumn('nationality');
                }
            });
        }
    }
};
