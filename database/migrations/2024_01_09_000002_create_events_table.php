<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('max_participants');
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled', 'archived'])
                  ->default('upcoming');
            $table->boolean('is_private')->default(false);
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('event_categories')
                  ->nullOnDelete();
            $table->foreignId('organizer_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->string('timezone')->default('UTC');
            $table->text('cancellation_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
