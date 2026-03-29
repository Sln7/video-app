<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'artist'      => 'sometimes|nullable|string|max:255',
            'album'       => 'sometimes|nullable|string|max:255',
            'thumbnail'   => 'sometimes|nullable|mimes:jpeg,jpg,png|max:2000',
        ];
    }
}
