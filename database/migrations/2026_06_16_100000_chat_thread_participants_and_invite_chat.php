<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_thread_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('thread_id')->constrained('chat_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['thread_id', 'user_id']);
        });

        Schema::table('client_account_requests', function (Blueprint $table) {
            $table->boolean('add_to_chat')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('client_account_requests', function (Blueprint $table) {
            $table->dropColumn('add_to_chat');
        });

        Schema::dropIfExists('chat_thread_participants');
    }
};
