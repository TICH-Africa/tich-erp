<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('title', 300);
            $table->longText('body');
            $table->string('status', 50)->default('draft'); // draft, published, archived
            $table->dateTime('published_at')->nullable();
            $table->string('seo_meta_title', 300)->nullable();
            $table->string('seo_meta_description', 500)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        $now = now();

        DB::table('cms_pages')->insert([
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'body' => '<p>This Privacy Policy explains how TICH in Africa collects, uses, and protects personal information when you use our website and institutional platforms.</p><p>We collect information you provide during applications, registration, onboarding, and account use. We use that information to deliver education services, manage admissions and employment processes, communicate with you, and meet legal obligations.</p><p>We do not sell personal data. Access is limited to authorised staff and service providers who need it to support institutional operations. You may contact us to request access to or correction of your personal information where applicable.</p><p>This policy may be updated from time to time. The version published on this page is the current version.</p>',
                'status' => 'published',
                'published_at' => $now,
                'seo_meta_title' => 'Privacy Policy',
                'seo_meta_description' => 'How TICH in Africa collects, uses, and protects personal information.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms and Conditions',
                'body' => '<p>These Terms and Conditions govern your use of the TICH in Africa website and institutional platforms, including applications, student and staff portals, and related online services.</p><p>By creating an account, completing onboarding, or submitting an application, you agree to provide accurate information and to use the platform only for lawful institutional purposes.</p><p>Accounts and access credentials are personal. You are responsible for keeping them secure and for activity under your account. Content and materials on the platform remain the property of TICH in Africa or their respective owners.</p><p>We may update these terms periodically. Continued use of the platform after updates constitutes acceptance of the revised terms.</p>',
                'status' => 'published',
                'published_at' => $now,
                'seo_meta_title' => 'Terms and Conditions',
                'seo_meta_description' => 'Terms governing use of TICH in Africa websites and institutional platforms.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};
