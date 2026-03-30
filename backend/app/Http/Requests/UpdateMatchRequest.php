<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'home_goals' => 'required|integer|min:0',
            'away_goals' => 'required|integer|min:0',
        ];
    }
}
