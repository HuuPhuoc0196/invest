<?php

namespace App\Http\Requests;

class ImportStocksCsvRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'csv_file' => 'required|file|max:2048',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $file = $this->file('csv_file');
            if (!$file) return;
            if (!in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'])) {
                $validator->errors()->add('csv_file', 'File phải có đuôi .csv hoặc .txt');
                return;
            }
            $mime = $file->getMimeType();
            $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/octet-stream'];
            if ($mime && !in_array($mime, $allowedMimes)) {
                $validator->errors()->add('csv_file', 'File không đúng định dạng CSV.');
            }
        });
    }
}
