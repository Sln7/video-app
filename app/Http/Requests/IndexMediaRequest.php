<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'     => 'nullable|string',
            'media_type' => 'nullable|in:audio,video',
            'order'      => 'nullable|in:asc,desc',
            'order_by'   => 'nullable|in:title,views,created_at',
            'page'       => 'nullable|integer|min:1',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ];
    }
}
