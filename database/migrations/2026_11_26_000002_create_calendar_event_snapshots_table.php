<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_event_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('calendar_id');
            $table->string('event_id');
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('starts_at');
            $table->string('ends_at');
            $table->boolean('start_is_date')->default(false);
            $table->boolean('end_is_date')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'calendar_id', 'event_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('calendar_event_snapshots'); }
};
