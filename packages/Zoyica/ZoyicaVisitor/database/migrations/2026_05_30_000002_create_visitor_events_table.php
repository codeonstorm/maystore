<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zv_events', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 36)->index();
            $table->string('event_type', 50);
            $table->text('page_url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zv_events');
    }
};
