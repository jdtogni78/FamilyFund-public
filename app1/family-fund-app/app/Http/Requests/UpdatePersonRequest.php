<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Person;
use Illuminate\Support\Facades\Log;
class UpdatePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->route('person');
        Log::info("validating person $id");
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:persons,email,'.$id.',id,deleted_at,NULL',
            'birthday' => 'required|date',
            'phones' => 'required|array|min:1',
            'phones.*.number' => 'required|string|max:30',
            'phones.*.type' => 'required|in:mobile,home,work,other',
            'addresses.*.street' => 'required|string|max:255',
            'addresses.*.number' => 'nullable|string|max:20',
            'addresses.*.county' => 'nullable|string|max:255',
            'addresses.*.city' => 'required|string|max:255',
            'addresses.*.state' => 'required|string|max:2',
            'addresses.*.zip_code' => 'required|string|max:10',
            'documents.*.type' => 'required|in:CPF,RG,CNH,Passport,SSN,other',
            'documents.*.number' => 'required|string|max:50',
        ];
    }
} 