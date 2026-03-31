<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'share_token' => $this->when($this->isOwnedBy($request->user()), $this->share_token),
            'share_url' => $this->when($this->share_token, fn () => url("/shared/playlist/{$this->share_token}")),
            'media_count' => $this->whenCounted('media'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'user' => $this->whenLoaded('user', fn () => [
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function isOwnedBy($user): bool
    {
        return $user && $this->user_id === $user->id;
    }
}
