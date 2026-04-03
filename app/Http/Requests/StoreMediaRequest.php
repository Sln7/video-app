<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreMediaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('source') === 'video_to_audio') {
            $this->merge(['media_type' => 'audio']);
        }

        if (! in_array($this->input('source'), ['youtube', 'youtube_to_audio'], true)) {
            return;
        }

        $videoId = $this->extractYouTubeId((string) $this->input('video_id', ''));
        if ($videoId) {
            $this->merge(['video_id' => $videoId]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title'       => 'required_unless:source,youtube,video_to_audio,youtube_to_audio|string|max:255',
            'description' => 'nullable|string',
            'media_type'  => 'required|in:audio,video',
            'source'      => 'required|in:youtube,hls,local_audio,video_to_audio,youtube_to_audio',
            'video_id'    => 'required_if:source,youtube,youtube_to_audio|string|regex:/^[A-Za-z0-9_-]{11}$/|unique:media,video_id',
            'thumbnail'   => 'nullable|mimes:jpeg,jpg,png|max:2000',
        ];

        if (in_array($this->input('source'), ['hls', 'local_audio', 'video_to_audio'])) {
            if ($this->input('source') === 'video_to_audio') {
                $rules['file'] = 'required|file|mimes:mp4,mov,ogg,qt,mkv,webm|max:200000';
            } elseif ($this->input('media_type') === 'audio') {
                $rules['file'] = 'required|file|mimes:mp3,wav,ogg,flac,aac|max:50000';
            } else {
                $rules['file'] = 'required|file|mimes:mp4,mov,ogg,qt|max:200000';
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required_unless'  => 'Title is required when source is not YouTube.',
            'media_type.required'    => 'Media type (audio or video) is required.',
            'media_type.in'          => 'Media type must be audio or video.',
            'source.required'        => 'Source is required.',
            'source.in'              => 'Source must be youtube, hls, local_audio, video_to_audio, or youtube_to_audio.',
            'video_id.required_if'   => 'YouTube video ID is required when source is youtube or youtube_to_audio.',
            'video_id.regex'         => 'YouTube video ID must be a valid 11-character ID or URL.',
            'video_id.unique'        => 'This media has already been added.',
            'file.required'          => 'A media file is required for this source type.',
            'file.mimes'             => 'Invalid file type for the selected media type.',
            'file.max'               => 'File size exceeds the maximum allowed limit.',
            'thumbnail.mimes'        => 'Thumbnail must be jpeg, jpg, or png.',
            'thumbnail.max'          => 'Thumbnail must not exceed 2MB.',
        ];
    }

    private function extractYouTubeId(string $value): ?string
    {
        $candidate = trim($value);

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate)) {
            return $candidate;
        }

        if (Str::startsWith($candidate, 'www.')) {
            $candidate = 'https://'.$candidate;
        }

        $url = parse_url($candidate);
        if (! is_array($url)) {
            return null;
        }

        $host = strtolower($url['host'] ?? '');
        $path = trim($url['path'] ?? '', '/');

        if (str_contains($host, 'youtu.be')) {
            $id = explode('/', $path)[0] ?? '';

            return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
        }

        if (str_contains($host, 'youtube.com')) {
            parse_str($url['query'] ?? '', $query);
            $id = $query['v'] ?? null;

            if (! $id && $path !== '') {
                $parts = explode('/', $path);
                if (in_array($parts[0] ?? '', ['embed', 'shorts', 'live'], true)) {
                    $id = $parts[1] ?? null;
                }
            }

            return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
        }

        return null;
    }
}
