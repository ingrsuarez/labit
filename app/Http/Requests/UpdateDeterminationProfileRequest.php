<?php

namespace App\Http\Requests;

use App\Enums\DeterminationProfileLabType;
use App\Models\Test;
use App\Support\DeterminationProfileTestMatcher;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeterminationProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('determination-profiles.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['integer', 'distinct', 'exists:tests,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $profile = $this->route('determination_profile');
            $labType = $profile?->lab_type;
            if (! $labType instanceof DeterminationProfileLabType || ! is_array($this->input('test_ids'))) {
                return;
            }

            foreach ($this->input('test_ids', []) as $testId) {
                $test = Test::find($testId);
                if (! $test || ! DeterminationProfileTestMatcher::matches($test, $labType)) {
                    $validator->errors()->add(
                        'test_ids',
                        'La determinación '.$testId.' no corresponde al tipo de laboratorio del perfil.'
                    );
                    break;
                }
            }
        });
    }
}
