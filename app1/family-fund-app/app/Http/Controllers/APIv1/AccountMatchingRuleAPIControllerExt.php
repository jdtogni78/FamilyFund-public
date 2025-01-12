<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateAccountMatchingRuleAPIRequest;
use App\Http\Requests\API\UpdateAccountMatchingRuleAPIRequest;
use App\Models\AccountMatchingRule;
use App\Repositories\AccountMatchingRuleRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\AccountMatchingRuleResource;
use Response;
use App\Http\Controllers\Traits\MailTrait;
use App\Mail\AccountMatchingRuleEmail;

/**
 * Class AccountMatchingRuleController
 * @package App\Http\Controllers\API
 */

class AccountMatchingRuleAPIControllerExt extends AccountMatchingRuleAPIController
{
    use MailTrait;

    public function store(CreateAccountMatchingRuleAPIRequest $request)
    {
        $input = $request->all();

        $accountMatchingRule = $this->accountMatchingRuleRepository->create($input);

        $api = [
            'mr' => $accountMatchingRule->matchingRule()->first(),
            'account' => $accountMatchingRule->account()->first(),
        ];

        $to = $accountMatchingRule->account()->first()->email_cc;
        $mail = new AccountMatchingRuleEmail($accountMatchingRule, $api);
        $this->sendMail($mail, $to);

        return $this->sendResponse(new AccountMatchingRuleResource($accountMatchingRule), 'Account Matching Rule saved successfully');
    }
}
