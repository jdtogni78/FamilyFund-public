<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\FundReportController;
use App\Http\Controllers\Traits\FundTrait;
use App\Http\Requests\CreateFundReportRequest;
use App\Repositories\FundReportRepository;
use Laracasts\Flash\Flash;
use Response;

class FundReportControllerExt extends FundReportController
{
    use FundTrait;

    public function __construct(FundReportRepository $fundReportRepo)
    {
        parent::__construct($fundReportRepo);
    }

    /**
     * Store a newly created FundReport in storage.
     *
     * @param CreateFundReportRequest $request
     *
     * @return Response
     */
    public function store(CreateFundReportRequest $request)
    {
        $input = $request->all();

        $this->createAndSendFundReport($input);

        if (count($this->err) == 0) {
            Flash::success('Fund Report saved successfully.');
        } else {
            Flash::error(implode(",", $this->err));
        }
        return redirect(route('fundReports.index'));
    }


}
