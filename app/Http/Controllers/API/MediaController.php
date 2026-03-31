<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexMediaRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Resources\MediaCollection;
use App\Http\Resources\MediaResource;
use App\Jobs\IncrementViews;
use App\Jobs\UpdateLikesCount;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    public function __construct(private MediaService $mediaService) {}

    public function index(IndexMediaRequest $request)
    {
        $params   = $request->validated();
        $cacheKey = 'media_index_'.md5(json_encode($params));

        $media = Cache::tags(['media_index'])->remember($cacheKey, now()->addMinutes(10), function () use ($params) {
            return Media::filter($params)
                ->orderBy($params['order_by'] ?? 'created_at', $params['order'] ?? 'desc')
                ->paginate($params['per_page'] ?? 10);
        });

        return new MediaCollection($media);
    }

    public function show(string $id)
    {
        try {
            $cacheKey = 'media_show_'.$id;

            $media = Cache::tags(['media_show', "media_{$id}"])->remember($cacheKey, now()->addMinutes(10), function () use ($id) {
                return $this->mediaService->getByPublicId($id);
            });

            if (! $media->processed) {
                return response()->json([
                    'message' => 'Media is still being processed.',
                    'data'    => new MediaResource($media),
                    'status'  => 'processing',
                ]);
            }

            $viewsCacheKey = "media:{$media->id}:views";
            if (! Cache::has($viewsCacheKey)) {
                Cache::put($viewsCacheKey, $media->views, now()->addDays(1));
            }
            Cache::increment($viewsCacheKey);
            IncrementViews::dispatch($media);

            $likesCacheKey = "media:{$media->id}:likes";
            if (! Cache::has($likesCacheKey)) {
                Cache::put($likesCacheKey, $media->likes, now()->addDays(1));
            }

            $media->views = Cache::get($viewsCacheKey);
            $media->likes = Cache::get($likesCacheKey);

            return new MediaResource($media);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Media not found.'], 404);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => 'Error processing request.'], 500);
        }
    }

    public function store(StoreMediaRequest $request)
    {
        try {
            $media = $this->mediaService->create($request->validated(), $request);

            Cache::put("media:{$media->id}:views", 0, now()->addDays(1));
            Cache::put("media:{$media->id}:likes", 0, now()->addDays(1));
            Cache::tags(['media_index'])->flush();

            return new MediaResource($media);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error('Media creation failed', [
                'source' => $request->input('source'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Error creating media: ' . $e->getMessage()], 500);
        }
    }

    public function toggleFavorite(Request $request, string $id)
    {
        try {
            $media = $this->mediaService->getByPublicId($id);
            $user  = $request->user();

            $likesCacheKey = "media:{$media->id}:likes";
            if (! Cache::has($likesCacheKey)) {
                Cache::put($likesCacheKey, $media->likes, now()->addDays(1));
            }

            $isFavorited = $media->favorites()->where('user_id', $user->id)->exists();

            if ($isFavorited) {
                $media->favorites()->detach($user->id);
                UpdateLikesCount::dispatch($media->id, -1);
                Cache::decrement($likesCacheKey);
                $message = 'Removed from favorites.';
            } else {
                $media->favorites()->attach($user->id);
                UpdateLikesCount::dispatch($media->id, 1);
                Cache::increment($likesCacheKey);
                $message = 'Added to favorites.';
            }

            return response()->json([
                'message' => $message,
                'likes'   => Cache::get($likesCacheKey),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Media not found.'], 404);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => 'Error processing request.'], 500);
        }
    }
}
