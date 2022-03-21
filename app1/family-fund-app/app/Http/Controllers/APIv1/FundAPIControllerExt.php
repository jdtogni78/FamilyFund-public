<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\Traits\FundTrait;
use App\Models\Fund;
use App\Models\FundExt;
use App\Repositories\FundRepository;
use App\Http\Controllers\AppBaseController;
use Response;

/**
 * Class FundControllerExt
 * @package App\Http\Controllers\API
 */

class FundAPIControllerExt extends AppBaseController
{
    use FundTrait;
    protected $fundRepository;

    public function __construct(FundRepository $fundRepo)
    {
        $this->fundRepository = $fundRepo;
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
        /** @var Fund $fund */
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            return $this->sendError('Fund not found');
        }

        $arr = $this->createFullFundResponse($fund, $asOf, $this->isAdmin());

        return $this->sendResponse($arr, 'Fund retrieved successfully');
    }

}
