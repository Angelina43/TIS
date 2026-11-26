<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'files' => 'mimes:doc,pdf,docx,zip,jpeg,jpg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'files.mimes' => 'file must be a doc, pdf, docx, zip, jpeg, jpg, png',
            'files.max' => 'file is too large, maximum size is 2MB',
        ];
    }
}
