<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\AccountMatchingRule;

class CreateAccountMatchingRuleRequestBulk extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = AccountMatchingRule::$rules;
        $rules['account_ids'] = 'required|array:integer';
        $rules['matching_rule_id'] = 'required|integer';
        return $rules;
    }
}
