<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_kind', 32)->default('team');
            $table->foreignUlid('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('note_type', 32)->default('general');
            $table->string('title')->nullable();
            $table->text('body');
            $table->date('noted_on');
            $table->time('start_time')->nullable();
            $table->string('meeting_url')->nullable();
            $table->boolean('visible_to_client')->default(false);
            $table->timestamps();

            $table->index(['client_id', 'noted_on']);
            $table->index(['client_id', 'visible_to_client']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_notes');
    }
};
