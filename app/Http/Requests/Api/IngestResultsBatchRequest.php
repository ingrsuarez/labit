<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IngestResultsBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->get('api_client') !== null;
    }

    public function rules(): array
    {
        return [
            'batch_id' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.external_message_id' => ['nullable', 'string', 'max:100'],
            'items.*.hl7_control_id' => ['required', 'string', 'max:100'],
            'items.*.protocol_number' => ['required', 'string', 'max:50'],
            'items.*.equipment_name' => ['nullable', 'string', 'max:100'],
            'items.*.results' => ['required', 'array', 'min:1'],
            'items.*.results.*.labit_test_id' => ['required', 'integer', 'exists:tests,id'],
            'items.*.results.*.value' => ['required', 'string', 'max:255'],
            'items.*.results.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.results.*.reference_range' => ['nullable', 'string', 'max:100'],
            'items.*.results.*.abnormal_flag' => ['nullable', 'string', 'max:10'],
            'items.*.results.*.obx_index' => ['required', 'integer', 'min:0'],
        ];
    }
}
