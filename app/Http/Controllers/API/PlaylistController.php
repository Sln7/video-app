<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlaylistRequest;
use App\Http\Requests\UpdatePlaylistRequest;
use App\Http\Resources\PlaylistResource;
use App\Models\Media;
use App\Models\Playlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    public function index(Request $request)
    {
        $playlists = Playlist::query()
            ->where('user_id', $request->user()->id)
            ->withCount('media')
            ->latest()
            ->paginate(10);

        return PlaylistResource::collection($playlists);
    }

    public function store(StorePlaylistRequest $request): JsonResponse
    {
        $playlist = Playlist::create([
            'user_id' => $request->user()->id,
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        return response()->json([
            'message' => 'Playlist created successfully.',
            'data' => new PlaylistResource($playlist->loadCount('media')),
        ], 201);
    }

    public function show(Request $request, string $publicId): PlaylistResource
    {
        $playlist = $this->findOwnedPlaylist($request->user()->id, $publicId, true);

        return new PlaylistResource($playlist);
    }

    public function update(UpdatePlaylistRequest $request, string $publicId): JsonResponse
    {
        $playlist = $this->findOwnedPlaylist($request->user()->id, $publicId);

        $playlist->update($request->validated());

        return response()->json([
            'message' => 'Playlist updated successfully.',
            'data' => new PlaylistResource($playlist->loadCount('media')),
        ]);
    }

    public function destroy(Request $request, string $publicId): JsonResponse
    {
        $playlist = $this->findOwnedPlaylist($request->user()->id, $publicId);
        $playlist->delete();

        return response()->json([
            'message' => 'Playlist deleted successfully.',
        ]);
    }

    public function attachMedia(Request $request, string $publicId, string $mediaPublicId): JsonResponse
    {
        $playlist = $this->findOwnedPlaylist($request->user()->id, $publicId);
        $media = Media::findByPublicId($mediaPublicId);

        $playlist->media()->syncWithoutDetaching([$media->id]);

        return response()->json([
            'message' => 'Media added to playlist.',
            'data' => new PlaylistResource($playlist->load(['media'])->loadCount('media')),
        ]);
    }

    public function detachMedia(Request $request, string $publicId, string $mediaPublicId): JsonResponse
    {
        $playlist = $this->findOwnedPlaylist($request->user()->id, $publicId);
        $media = Media::findByPublicId($mediaPublicId);

        $playlist->media()->detach($media->id);

        return response()->json([
            'message' => 'Media removed from playlist.',
            'data' => new PlaylistResource($playlist->load(['media'])->loadCount('media')),
        ]);
    }

    private function findOwnedPlaylist(int $userId, string $publicId, bool $withMedia = false): Playlist
    {
        $query = Playlist::query()->where('user_id', $userId)->where('public_id', $publicId);

        if ($withMedia) {
            $query->with('media')->withCount('media');
        }

        return $query->firstOrFail();
    }
}
