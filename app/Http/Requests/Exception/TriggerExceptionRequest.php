<?php

declare(strict_types=1);

namespace App\Http\Requests\Exception;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TriggerExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['type' => ['required', 'string', 'in:domain,runtime,logic,invalid_argument,bad_method'], 'message' => ['nullable', 'string', 'max:500']];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422)
        );
    }
}
