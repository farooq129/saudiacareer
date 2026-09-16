<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A seeker applying to a listing.
 *
 * `source` records how they applied, because the two routes give the employer
 * very different things: an on-site application carries a CV and a covering
 * letter, while a WhatsApp tap only proves the seeker opened the conversation.
 * Counting both in `jobs.applications_count` would flatter the listing, so the
 * distinction is kept here and the count only follows on-site applications.
 *
 * The CV is copied onto the application rather than referenced from the
 * seeker's profile: the employer must keep seeing the CV as it was sent, even
 * after the seeker uploads a new one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('source', ['site', 'whatsapp', 'phone', 'email'])->default('site');

            $table->string('cv_path')->nullable();
            $table->text('cover_letter')->nullable();

            // Was the covering letter written by the AI tool? Employers asked
            // to be able to tell, and the AI tools page promises to disclose it.
            $table->boolean('cover_letter_ai_generated')->default(false);

            $table->enum('status', ['sent', 'viewed', 'shortlisted', 'rejected', 'hired'])
                ->default('sent')->index();

            // When the employer first opened it — drives the "usually reply
            // within three days" promise on the confirmation dialog.
            $table->timestamp('viewed_at')->nullable();

            // The employer's private note on this applicant.
            $table->text('employer_note')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            // One application per seeker per listing.
            $table->unique(['job_id', 'user_id']);
            // The employer's applicant inbox for one listing.
            $table->index(['job_id', 'status', 'created_at']);
            // The seeker's own "jobs I applied to" list.
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
