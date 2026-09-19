<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class ParseBibRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required_without:file', 'prohibits:file', 'string', 'max:100000'],
            'file' => ['required_without:text', 'prohibits:text', 'file', 'mimes:bib,txt', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required_without' => 'Передайте текст BibTeX или файл.',
            'file.required_without' => 'Передайте текст BibTeX или файл.',

            'text.prohibits' => 'Нельзя отправлять одновременно текст и файл.',
            'file.prohibits' => 'Нельзя отправлять одновременно файл и текст.',

            'text.string' => 'Поле text должно быть строкой.',
            'text.max' => 'Текст слишком большой.',

            'file.file' => 'Поле file должно быть файлом.',
            'file.mimes' => 'Файл должен быть в формате .bib или .txt.',
            'file.max' => 'Файл слишком большой.',
        ];
    }

    public function bibContent(): string
    {
        if ($this->hasFile('file')) {
            return file_get_contents($this->file('file')->getRealPath());
        }

        return $this->input('text');
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Переданные данные некорректны.',
            'errors' => $validator->errors(),
        ], 422));
    }

}
