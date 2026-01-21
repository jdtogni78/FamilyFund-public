<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\Traits\FundTrait;
use App\Http\Controllers\Traits\FundSetupTrait;
use App\Http\Requests\API\CreateFundWithSetupAPIRequest;
use App\Models\Fund;
use App\Models\FundExt;
use App\Repositories\FundRepository;
use App\Repositories\TransactionRepository;
use App\Http\Controllers\AppBaseController;
use Response;

/**
 * Class FundControllerExt
 * @package App\Http\Controllers\API
 */

class FundAPIControllerExt extends AppBaseController
{
    use FundTrait;
    use FundSetupTrait;

    protected $fundRepository;
    protected $transactionRepository;

    public function __construct(FundRepository $fundRepo, TransactionRepository $transactionRepo)
    {
        $this->fundRepository = $fundRepo;
        $this->transactionRepository = $transactionRepo;
    }

    /**
     * Display the specified Fund.
     * GET|HEAD /funds/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $now = date('Y-m-d');
        return $this->showAsOf($id, $now);
    }

    /**
     * Display the specified Fund.
     * GET|HEAD /funds/{id}/as_of/{date}
     *
     * @param int $id
     *
     * @return Response
     */
    public function showAsOf($id, $asOf)
    {
        /** @var Fund $fund */
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            return $this->sendError('Fund not found');
        }

        $arr = $this->createFundResponse($fund, $asOf);
        return $this->sendResponse($arr, 'Fund retrieved successfully');
    }

    /**
     * Display the specified Fund.
     * GET|HEAD /funds/{id}/performance_as_of/{date}
     *
     * @param int $id
     *
     * @return Response
     */
    public function showPerformanceAsOf($id, $asOf)
    {
        /** @var Fund $fund */
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            return $this->sendError('Fund not found');
        }

        $arr = $this->createFundArray($fund, $asOf);
        $this->perfObject = $fund;
        $arr['monthly_performance'] = $this->createMonthlyPerformanceResponse($asOf);

        return $this->sendResponse($arr, 'Fund retrieved successfully');
    }

    /**
     * Display the specified Fund.
     * GET|HEAD /funds/{id}/account_balances_as_of/{date}
     *
     * @param int $id
     *
     * @return Response
     */
    public function showAccountBalancesAsOf($id, $asOf)
    {
        /** @var FundExt $fund */
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            return $this->sendError('Fund not found');
        }

        $arr = $this->createFundArray($fund, $asOf);
        $isAdmin = $this->isAdmin();
        if ($isAdmin) {
            $arr['admin'] = true;
            $arr['balances'] = $this->createAccountBalancesResponse($fund, $asOf);
        }

        return $this->sendResponse($arr, 'Fund retrieved successfully');
    }

    /**
     * Display the specified Fund.
     * GET|HEAD /funds/{id}/report_as_of/{date}
     *
     * @param int $id
     *
     * @return Response
     */
    public function showReportAsOf($id, $asOf)
    {
        /** @var FundExt $fund */
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            return $this->sendError('Fund not found');
        }

        $arr = $this->createFullFundResponse($fund, $asOf, $this->isAdmin());

        return $this->sendResponse($arr, 'Fund retrieved successfully');
    }

    /**
     * Create a fund with complete setup (account, portfolio, initial transaction)
     * POST /api/funds/setup
     *
     * @param CreateFundWithSetupAPIRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeWithSetup(CreateFundWithSetupAPIRequest $request)
    {
        $input = $request->all();
        $isDryRun = $request->input('dry_run', false);

        try {
            $setupData = $this->setupFund($input, $isDryRun);

            // Build response
            $response = [
                'fund' => [
                    'id' => $setupData['fund']->id,
                    'name' => $setupData['fund']->name,
                    'goal' => $setupData['fund']->goal,
                ],
                'account' => [
                    'id' => $setupData['account']->id,
                    'fund_id' => $setupData['account']->fund_id,
                    'user_id' => $setupData['account']->user_id,
                    'nickname' => $setupData['account']->nickname,
                    'code' => $setupData['account']->code,
                ],
                'portfolios' => array_map(function ($portfolio) {
                    return [
                        'id' => $portfolio->id,
                        'fund_id' => $portfolio->fund_id,
                        'source' => $portfolio->source,
                    ];
                }, $setupData['portfolios']),
            ];

            if ($setupData['transaction']) {
                $response['transaction'] = [
                    'id' => $setupData['transaction']->id,
                    'account_id' => $setupData['transaction']->account_id,
                    'type' => $setupData['transaction']->type,
                    'amount' => $setupData['transaction']->value,
                    'shares' => $setupData['transaction']->shares,
                    'description' => $setupData['transaction']->descr,
                    'timestamp' => $setupData['transaction']->timestamp,
                ];

                if ($setupData['accountBalance']) {
                    $response['account_balance'] = [
                        'balance' => $setupData['accountBalance']->balance,
                        'shares' => $setupData['accountBalance']->shares,
                        'share_value' => $setupData['accountBalance']->share_value,
                    ];
                }
            }

            if ($isDryRun) {
                $response['dry_run'] = true;
                $response['note'] = 'Preview mode - no changes were saved to database';
            }

            $message = $isDryRun
                ? 'Fund setup preview generated successfully'
                : 'Fund created successfully with account, portfolio, and initial transaction';

            return $this->sendResponse($response, $message);
        } catch (\Exception $e) {
            return $this->sendError('Fund setup failed: ' . $e->getMessage());
        }
    }

}
