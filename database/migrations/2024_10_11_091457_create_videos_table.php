<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('title');
            $table->enum('media_type', ['audio', 'video'])->default('video');
            $table->text('description')->nullable();
            $table->string('embed_url')->nullable();
            $table->string('video_id')->unique()->nullable();
            $table->string('video_path')->nullable();
            $table->string('hls_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->integer('views')->default(0);
            $table->integer('likes')->default(0);
            $table->boolean('processed')->default(false);
            $table->enum('source', ['youtube', 'hls', 'local_audio', 'soundcloud']);
            $table->string('artist')->nullable();
            $table->string('album')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
