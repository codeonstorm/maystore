<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zv_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 36)->unique();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device', 20)->default('desktop');
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->text('referrer')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->unsignedInteger('page_count')->default(0);
            $table->unsignedInteger('event_count')->default(0);
            $table->boolean('is_converted')->default(false);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zv_sessions');
    }
};
