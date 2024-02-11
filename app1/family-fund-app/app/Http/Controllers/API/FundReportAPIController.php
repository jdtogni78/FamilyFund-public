<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateFundReportAPIRequest;
use App\Http\Requests\API\UpdateFundReportAPIRequest;
use App\Models\FundReport;
use App\Repositories\FundReportRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\FundReportResource;
use Response;

/**
 * Class FundReportController
 * @package App\Http\Controllers\API
 */

class FundReportAPIController extends AppBaseController
{
    /** @var  FundReportRepository */
    public FundReportRepository $fundReportRepository;

    public function __construct(FundReportRepository $fundReportRepo)
    {
        $this->fundReportRepository = $fundReportRepo;
    }

    /**
     * Display a listing of the FundReport.
     * GET|HEAD /fundReports
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $fundReports = $this->fundReportRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(FundReportResource::collection($fundReports), 'Fund Reports retrieved successfully');
    }

    /**
     * Store a newly created FundReport in storage.
     * POST /fundReports
     *
     * @param CreateFundReportAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateFundReportAPIRequest $request)
    {
        $input = $request->all();

        $fundReport = $this->fundReportRepository->create($input);

        return $this->sendResponse(new FundReportResource($fundReport), 'Fund Report saved successfully');
    }

    /**
     * Display the specified FundReport.
     * GET|HEAD /fundReports/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var FundReport $fundReport */
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            return $this->sendError('Fund Report not found');
        }

        return $this->sendResponse(new FundReportResource($fundReport), 'Fund Report retrieved successfully');
    }

    /**
     * Update the specified FundReport in storage.
     * PUT/PATCH /fundReports/{id}
     *
     * @param int $id
     * @param UpdateFundReportAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateFundReportAPIRequest $request)
    {
        $input = $request->all();

        /** @var FundReport $fundReport */
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            return $this->sendError('Fund Report not found');
        }

        $fundReport = $this->fundReportRepository->update($input, $id);

        return $this->sendResponse(new FundReportResource($fundReport), 'FundReport updated successfully');
    }

    /**
     * Remove the specified FundReport from storage.
     * DELETE /fundReports/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var FundReport $fundReport */
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            return $this->sendError('Fund Report not found');
        }

        $fundReport->delete();

        return $this->sendSuccess('Fund Report deleted successfully');
    }
}
