<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the FK in video_likes before renaming the referenced table
        Schema::table('video_likes', function (Blueprint $table) {
            $table->dropForeign(['video_id']);
        });

        Schema::rename('videos', 'media');

        Schema::table('media', function (Blueprint $table) {
            // Add media_type before modifying source
            $table->enum('media_type', ['audio', 'video'])->default('video')->after('title');

            // Expand the source enum: drop + recreate with new values
            $table->dropColumn('source');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->enum('source', ['youtube', 'hls', 'local_audio', 'soundcloud'])->after('processed');

            // Audio metadata columns
            $table->string('artist')->nullable()->after('source');
            $table->string('album')->nullable()->after('artist');
            $table->integer('duration_seconds')->default(0)->after('album');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'artist', 'album', 'duration_seconds', 'source']);
        });

        Schema::table('media', function (Blueprint $table) {
            $table->enum('source', ['youtube', 'hls'])->after('processed');
        });

        Schema::rename('media', 'videos');

        Schema::table('video_likes', function (Blueprint $table) {
            $table->foreign('video_id')->references('id')->on('videos')->onDelete('cascade');
        });
    }
};
