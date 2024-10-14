<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexVideoRequest;
use App\Http\Requests\StoreVideoRequest;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoResource;
use App\Jobs\IncrementViews;
use App\Jobs\UpdateLikesCount;
use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
{
    protected $videoService;

    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    public function index(IndexVideoRequest $request)
    {
        $params = $request->validated();
        $cacheKey = 'videos_index_'.md5(json_encode($params));

        $videos = Cache::tags(['videos_index'])->remember($cacheKey, now()->addMinutes(10), function () use ($params) {
            return Video::filter($params)
                ->orderBy($params['sort'] ?? 'created_at', $params['order'] ?? 'desc')
                ->paginate($params['per_page'] ?? 10);
        });

        return new VideoCollection($videos);
    }

    public function show(string $id)
    {
        try {
            $cacheKey = 'video_show_'.$id;

            $video = Cache::tags(['video_show', "video_{$id}"])->remember($cacheKey, now()->addMinutes(10), function () use ($id) {
                return $this->videoService->getVideoByPublicId($id);
            });

            if ($video->processed === false) {
                return response()->json([
                    'message' => 'O vídeo ainda está sendo processado, por favor, aguarde.',
                    'data' => new VideoResource($video),
                    'status' => 'processing',
                ]);
            }

            $viewsCacheKey = "video:{$video->id}:views";
            if (! Cache::has($viewsCacheKey)) {
                Cache::put($viewsCacheKey, $video->views, now()->addDays(1));
            }

            Cache::increment($viewsCacheKey);

            IncrementViews::dispatch($video);

            $likesCacheKey = "video:{$video->id}:likes";
            if (! Cache::has($likesCacheKey)) {
                Cache::put($likesCacheKey, $video->likes, now()->addDays(1));
            }

            $video->views = Cache::get($viewsCacheKey);
            $video->likes = Cache::get($likesCacheKey);

            return new VideoResource($video);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Vídeo não encontrado'], 404);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => 'Erro ao processar a requisição'], 500);
        }
    }

    public function store(StoreVideoRequest $request)
    {
        try {
            $data = $request->validated();

            $video = $this->videoService->createVideo($data, $request);

            $viewsCacheKey = "video:{$video->id}:views";
            $likesCacheKey = "video:{$video->id}:likes";
            Cache::put($viewsCacheKey, 0, now()->addDays(1));
            Cache::put($likesCacheKey, 0, now()->addDays(1));

            Cache::tags(['videos_index'])->flush();

            return new VideoResource($video);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => 'Erro ao criar o vídeo'], 500);
        }
    }

    public function toggleLike(Request $request, $id)
    {
        $video = $this->videoService->getVideoByPublicId($id);
        $user = $request->user();

        $likesCacheKey = "video:{$video->id}:likes";

        if (! Cache::has($likesCacheKey)) {
            Cache::put($likesCacheKey, $video->likes, now()->addDays(1));
        }

        $userLiked = $video->likes()->where('user_id', $user->id)->exists();

        if ($userLiked) {
            $video->likes()->detach($user->id);
            $message = 'Vídeo descurtido com sucesso';

            UpdateLikesCount::dispatch($video->id, -1);

            Cache::decrement($likesCacheKey);
        } else {
            $video->likes()->attach($user->id);
            $message = 'Vídeo curtido com sucesso';

            UpdateLikesCount::dispatch($video->id, 1);

            Cache::increment($likesCacheKey);
        }

        return response()->json([
            'message' => $message,
            'likes' => Cache::get($likesCacheKey),
        ]);
    }
}
