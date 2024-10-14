<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required_if:source,hls|string|max:255',
            'description' => 'nullable|string',
            'source' => 'required|in:youtube,hls',
            'video_id' => 'required_if:source,youtube|string|unique:videos,video_id',
            'video' => 'required_if:source,hls|mimes:mp4,mov,ogg,qt|max:200000',
            'thumbnail' => 'nullable|mimes:jpeg,jpg,png|max:2000',
        ];
    }

    public function messages()
    {
        return [
            'title.required_if' => 'O título é obrigatório quando a origem é um upload de vídeo.',
            'source.required' => 'A origem do vídeo é obrigatória.',
            'source.in' => 'A origem do vídeo deve ser youtube ou hls.',
            'video_id.required_if' => 'O ID é obrigatório quando a origem é um provedor externo.',
            'video_id.unique' => 'Este vídeo já foi cadastrado anteriormente.',
            'video.required_if' => 'O upload do vídeo é obrigatório quando não houver um provedor externo.',
            'video.mimes' => 'O arquivo de vídeo deve ser do tipo: mp4, mov, ogg, qt.',
            'video.max' => 'O arquivo de vídeo deve ter no máximo 200MB.',
            'thumbnail.mimes' => 'O arquivo de imagem deve ser do tipo: jpeg, jpg, png.',
            'thumbnail.max' => 'O arquivo de imagem deve ter no máximo 2MB.',
        ];
    }
}
