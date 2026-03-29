<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('video_likes', 'media_favorites');

        Schema::table('media_favorites', function (Blueprint $table) {
            $table->renameColumn('video_id', 'media_id');
        });

        Schema::table('media_favorites', function (Blueprint $table) {
            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('media_favorites', function (Blueprint $table) {
            $table->dropForeign(['media_id']);
            $table->renameColumn('media_id', 'video_id');
        });

        Schema::rename('media_favorites', 'video_likes');

        Schema::table('video_likes', function (Blueprint $table) {
            $table->foreign('video_id')->references('id')->on('videos')->onDelete('cascade');
        });
    }
};
