<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('changelog_reads')) {
            return;
        }

        Schema::create('changelog_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('changelog_slug');
            $table->dateTime('read_at')->nullable();
            $table->boolean('modal_dismissed')->default(false);
            $table->unique(['user_id', 'changelog_slug'], 'cl_read_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelog_reads');
    }
};
