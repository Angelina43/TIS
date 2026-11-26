<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'email' => 'required|unique:users',
            'password' => 'required|min:3|regex:/^[a-zA-Z\0-9]+$/u',
            'first_name' => 'required|min:2|regex:/^[a-zA-Z]+$/u',
            'last_name' => 'required|regex:/^[a-zA-Z_-]+$/u',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.unique' => 'Email already exists',
            'email.email' => 'Invalid email address',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 3 characters',
            'password.regex' => 'Invalid password',
            'first_name.required' => 'First name is required',
            'first_name.min' => 'First name must be at least 2 characters',
            'first_name.regex' => 'Invalid first name',
            'last_name.required' => 'Last name is required',
            'last_name.regex' => 'Invalid last name',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json($validator->errors(), 400)
        );
    }
}
