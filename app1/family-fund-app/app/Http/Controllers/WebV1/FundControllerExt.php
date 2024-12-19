<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\Traits\ChartBaseTrait;
use App\Http\Controllers\Traits\FundPDF;
use App\Repositories\FundRepository;
use Laracasts\Flash\Flash;
use Mockery\Exception;
use Response;
use App\Http\Controllers\FundController;
use App\Http\Controllers\Traits\FundTrait;
use Spatie\TemporaryDirectory\Exceptions\PathAlreadyExists;

class FundControllerExt extends FundController
{
    use FundTrait;
    use ChartBaseTrait;

    public function __construct(FundRepository $fundRepo)
    {
        parent::__construct($fundRepo);
    }

    /**
     * Display the specified Fund.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        return $this->showAsOf($id, null);
    }

    /**
     * Display the specified Fund.
     *
     * @param int $id
     *
     * @return Response
     */
    public function showAsOf($id, $asOf=null)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $arr = $this->createFullFundResponse($fund, $asOf, $this->isAdmin());

        return view('funds.show_ext')
            ->with('api', $arr)
            ->with('asOf', $arr['asOf']);
    }

    /**
     * Display the specified Fund.
     * @param int $id
     * @return Response
     * @throws PathAlreadyExists
     */
    public function showPDFAsOf($id, $asOf=null)
    {
        $debug_html = false;
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $isAdmin = $this->isAdmin();
        $arr = $this->createFullFundResponse($fund, $asOf, $isAdmin);
        $pdf = new FundPDF($arr, $isAdmin, $debug_html);

        return $pdf->inline('fund.pdf');
    }

    /**
     * Display the specified Fund.
     *
     * @param int $id
     *
     * @return Response
     */
    public function tradeBandsAsOf($id, $tport, $asOf)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $arr = $this->createFundResponseTradeBands($fund, $asOf, $this->isAdmin());

        return view('funds.show_trade_bands')
            ->with('api', $arr)
            ->with('asOf', $arr['asOf'])
            ->with('tport', $tport);
    }

}
