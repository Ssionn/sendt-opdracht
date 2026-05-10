<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notify_email' => ['required', 'boolean'],
            'notify_slack' => ['required', 'boolean'],
            'notify_sentry' => ['required', 'boolean'],
            'sentry_auth_token' => ['nullable', 'string'],
            'sentry_org_slug' => ['nullable', 'string'],
            'sentry_project_slug' => ['nullable', 'string'],
            'sentry_region' => ['nullable', 'string', 'in:us,de'],
            'sentry_dsn' => ['nullable', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422)
        );
    }
}
