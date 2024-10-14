<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexVideoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'search' => 'nullable|string',
            'order' => 'nullable|in:asc,desc',
            'order_by' => 'nullable|in:title,views,created_at',
            'page' => 'nullable|integer',
            'per_page' => 'nullable|integer',
        ];
    }

    public function messages()
    {
        return [
            'search.string' => 'O campo de pesquisa deve ser uma string.',
            'order.in' => 'O campo de ordenação deve ser asc ou desc.',
            'order_by.in' => 'O campo de ordenação deve ser title, views ou created_at.',
            'page.integer' => 'O campo de página deve ser um número inteiro.',
            'per_page.integer' => 'O campo por página deve ser um número inteiro.',
        ];
    }
}
