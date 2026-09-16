<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The "إبلاغ / Report" action on a listing.
 *
 * A free board attracts recruitment-fee scams and ads that are really visa
 * sales, so reporting is open to signed-out visitors too — `user_id` is
 * nullable and the IP is kept instead, which is also what makes flood control
 * possible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('reason', [
                'fake',           // وظيفة وهمية
                'fees',           // يطلب رسوماً من المتقدّم
                'duplicate',      // إعلان مكرر
                'filled',         // الوظيفة مشغولة
                'offensive',      // محتوى مسيء
                'wrong_category', // قسم خاطئ
                'other',
            ])->index();

            $table->text('note')->nullable();

            $table->enum('status', ['open', 'reviewing', 'upheld', 'dismissed'])
                ->default('open')->index();

            // What the admin did about it, kept for the audit trail even after
            // the listing itself is soft-deleted.
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            // The moderation queue: open reports, oldest first.
            $table->index(['status', 'created_at']);
            $table->index(['job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_reports');
    }
};
