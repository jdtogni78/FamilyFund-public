<?php

namespace App\Http\Controllers\APIv1;

use App\Models\Fund;
use App\Models\FundExt;
use App\Models\Utils;
use App\Repositories\FundRepository;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\FundResource;
use App\Repositories\PortfolioRepository;
use Response;
use App\Models\PerformanceTrait;

/**
 * Class FundControllerExt
 * @package App\Http\Controllers\API
 */

class FundAPIControllerExt extends AppBaseController
{
    use PerformanceTrait;

    protected $fundRepository;

    public function __construct(FundRepository $fundRepo)
    {
        $this->fundRepository = $fundRepo;
    }

    public function createFundResponse($fund, $asOf)
    {
        $this->perfObject = $fund;
        $rss = new FundResource($fund);
        $ret = $rss->toArray(NULL);

        $arr = array();
        $arr['value']                       = Utils::currency($value = $fund->valueAsOf($asOf));
        $arr['shares']                      = Utils::shares($shares = $fund->sharesAsOf($asOf));
        $arr['unallocated_shares']          = Utils::shares($unallocated = $fund->unallocatedShares($asOf));
        $arr['unallocated_shares_percent']  = Utils::percent($shares ? $unallocated/$shares : 0);
        $arr['allocated_shares']            = Utils::shares($allocated = $shares - $unallocated);
        $arr['allocated_shares_percent']    = Utils::percent($shares ? $allocated/$shares : 0);
        $arr['share_value']                 = Utils::currency($shares ? $value/$shares : 0);
        $ret['summary'] = $arr;

        return $ret;
    }

    public function createFundArray($fund, $asOf) {
        $this->perfObject = $fund;

        $arr = array();
        $arr['id'] = $fund->id;
        $arr['name'] = $fund->name;
        return $arr;
    }

    public function createAccountBalancesResponse(FundExt $fund, $asOf)
    {
        $bals = array();
        $sharePrice = $fund->shareValueAsOf($asOf);
        foreach ($fund->accountBalancesAsOf($asOf) as $balance) {
            $account = $balance->account()->first();
            $user = $account->user()->first();

            $bal = array();
            if ($user) {
                $bal['user'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            } else {
                continue;
                // $bal['user'] = [
                //     'id' => 0,
                //     'name' => 'N/A',
                // ];
            }
            $bal['account_id'] = $account->id;
            $bal['nickname'] = $account->nickname;
            $bal['type'] = $balance->type;
            $bal['shares'] = Utils::shares($balance->shares);
            $bal['value'] = Utils::currency($sharePrice * $balance->shares);
            $bals[] = $bal;
        }
        return $bals;
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
        $arr['as_of'] = $asOf;

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
        $arr['performance'] = $this->createMonthlyPerformanceResponse($asOf);
        $arr['as_of'] = $asOf;

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
        /** @var Fund $fund */
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            return $this->sendError('Fund not found');
        }

        $arr = $this->createFundArray($fund, $asOf);
        $arr['balances'] = $this->createAccountBalancesResponse($fund, $asOf);
        $arr['as_of'] = $asOf;

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

        $arr = $this->createFundResponse($fund, $asOf);
        $arr['performance'] = $this->createMonthlyPerformanceResponse($asOf);
        $arr['balances'] = $this->createAccountBalancesResponse($fund, $asOf);

        $portController = new PortfolioAPIControllerExt(\App::make(PortfolioRepository::class));
        $portfolio = $fund->portfolios()->first();
        $arr['portfolio'] = $portController->createPortfolioResponse($portfolio, $asOf);
        $arr['as_of'] = $asOf;

        return $this->sendResponse($arr, 'Fund retrieved successfully');
    }
}
