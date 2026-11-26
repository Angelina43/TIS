<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'required|unique:files,name,' . $id,
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'file is required',
            'name.unique' => 'name must be unique',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json($validator->errors(), 400)
        );
    }
}
