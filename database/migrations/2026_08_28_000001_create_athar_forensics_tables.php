<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('open')->index();
            $table->string('priority', 32)->default('medium')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('case_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role_in_case', 32)->default('member');
            $table->timestamps();
            $table->unique(['case_id', 'user_id']);
        });

        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->string('evidence_number', 64)->unique();
            $table->string('name');
            $table->string('original_filename')->nullable();
            $table->string('type', 64)->index();
            $table->string('storage_disk', 64)->default('private');
            $table->string('storage_key')->unique();
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type', 255)->nullable();
            $table->string('source', 255)->nullable();
            $table->string('status', 32)->default('registered')->index();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('evidence_hashes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained('evidence')->restrictOnDelete();
            $table->string('algorithm', 32)->default('sha256');
            $table->string('value', 128);
            $table->timestamp('calculated_at');
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['evidence_id', 'algorithm', 'value']);
        });

        Schema::create('chain_of_custody', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained('evidence')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 64)->index();
            $table->string('action', 128);
            $table->timestamp('occurred_at')->index();
            $table->json('metadata_json')->nullable();
            $table->string('previous_event_hash', 64)->nullable();
            $table->string('event_hash', 64)->unique();
            $table->timestamps();
        });

        Schema::create('tool_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('tool_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('adapter_key')->unique();
            $table->string('verification_state', 48)->default('cataloged')->index();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('token_hash', 255)->unique();
            $table->string('hostname')->nullable();
            $table->string('status', 32)->default('offline')->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('capabilities_json')->nullable();
            $table->timestamps();
        });

        Schema::create('tool_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->restrictOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('version', 128)->nullable();
            $table->string('path', 512)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('status', 48)->default('cataloged')->index();
            $table->timestamps();
        });

        Schema::create('tool_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->restrictOnDelete();
            $table->string('operation_key');
            $table->json('input_schema_json')->nullable();
            $table->json('output_schema_json')->nullable();
            $table->json('evidence_types_json')->nullable();
            $table->unsignedInteger('timeout_seconds')->default(300);
            $table->unsignedInteger('max_output_bytes')->default(10485760);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
            $table->unique(['tool_id', 'operation_key']);
        });

        Schema::create('analysis_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('evidence_id')->constrained('evidence')->restrictOnDelete();
            $table->foreignId('tool_id')->constrained('tools')->restrictOnDelete();
            $table->foreignId('operation_id')->constrained('tool_operations')->restrictOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('status', 32)->default('created')->index();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('exit_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('analysis_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_job_id')->constrained('analysis_jobs')->restrictOnDelete();
            $table->json('parameters_json');
            $table->timestamps();
        });

        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_job_id')->constrained('analysis_jobs')->restrictOnDelete();
            $table->string('raw_storage_key')->nullable();
            $table->json('parsed_json')->nullable();
            $table->string('parser_version', 64)->nullable();
            $table->string('tool_version', 128)->nullable();
            $table->string('adapter_version', 64)->nullable();
            $table->string('result_hash', 64)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('evidence_id')->nullable()->constrained('evidence')->nullOnDelete();
            $table->foreignId('analysis_result_id')->nullable()->constrained('analysis_results')->nullOnDelete();
            $table->string('title');
            $table->string('severity', 32)->default('informational')->index();
            $table->longText('description');
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('evidence_id')->nullable()->constrained('evidence')->nullOnDelete();
            $table->foreignId('source_result_id')->nullable()->constrained('analysis_results')->nullOnDelete();
            $table->string('event_type', 128)->index();
            $table->timestamp('event_time')->index();
            $table->string('timezone', 64)->default('UTC');
            $table->text('description');
            $table->json('attributes_json')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->string('source_type', 64);
            $table->unsignedBigInteger('source_id');
            $table->string('model_name', 128);
            $table->string('prompt_version', 64);
            $table->json('response_json');
            $table->decimal('grounding_score', 5, 4)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->string('file_key')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['case_id', 'version']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 128)->index();
            $table->string('entity_type', 128)->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->uuid('request_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('ai_analyses');
        Schema::dropIfExists('timeline_events');
        Schema::dropIfExists('findings');
        Schema::dropIfExists('analysis_results');
        Schema::dropIfExists('analysis_parameters');
        Schema::dropIfExists('analysis_jobs');
        Schema::dropIfExists('tool_operations');
        Schema::dropIfExists('tool_versions');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('tools');
        Schema::dropIfExists('tool_categories');
        Schema::dropIfExists('chain_of_custody');
        Schema::dropIfExists('evidence_hashes');
        Schema::dropIfExists('evidence');
        Schema::dropIfExists('case_user');
        Schema::dropIfExists('cases');
    }
};
