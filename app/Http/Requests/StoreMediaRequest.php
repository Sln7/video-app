<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title'       => 'required_unless:source,youtube|string|max:255',
            'description' => 'nullable|string',
            'media_type'  => 'required|in:audio,video',
            'source'      => 'required|in:youtube,hls,local_audio,soundcloud',
            'video_id'    => 'required_if:source,youtube|string|unique:media,video_id',
            'thumbnail'   => 'nullable|mimes:jpeg,jpg,png|max:2000',
        ];

        if (in_array($this->input('source'), ['hls', 'local_audio'])) {
            if ($this->input('media_type') === 'audio') {
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
            'source.in'              => 'Source must be youtube, hls, local_audio, or soundcloud.',
            'video_id.required_if'   => 'YouTube video ID is required when source is youtube.',
            'video_id.unique'        => 'This media has already been added.',
            'file.required'          => 'A media file is required for this source type.',
            'file.mimes'             => 'Invalid file type for the selected media type.',
            'file.max'               => 'File size exceeds the maximum allowed limit.',
            'thumbnail.mimes'        => 'Thumbnail must be jpeg, jpg, or png.',
            'thumbnail.max'          => 'Thumbnail must not exceed 2MB.',
        ];
    }
}
