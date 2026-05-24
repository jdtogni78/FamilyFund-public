<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\FundTrait;
use App\Http\Requests\API\CreateFundReportAPIRequest;
use App\Http\Requests\API\UpdateFundReportAPIRequest;
use App\Http\Resources\FundReportResource;
use App\Jobs\SendFundReport;
use App\Models\FundReport;
use App\Models\FundReportExt;
use App\Models\ScheduledJob;
use App\Repositories\FundReportRepository;
use App\Repositories\ScheduledJobRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FundReportAPIControllerExt
 * @package App\Http\Controllers\API
 */

class FundReportAPIControllerExt extends AppBaseController
{
    use FundTrait;

    /** @var  FundReportRepository */
    public FundReportRepository $fundReportRepository;

    public function __construct(FundReportRepository $fundReportRepo)
    {
        $this->fundReportRepository = $fundReportRepo;
    }

    public function store(CreateFundReportAPIRequest $request)
    {
        try {
            $input = $request->all();

            // Create fund report and validate emails
            $fundReport = FundReportExt::create($input);
            $this->validateReportEmails($fundReport);
            $fundReport->save();

            // Dispatch job to send emails
            SendFundReport::dispatch($fundReport);

            $result = new FundReportResource($fundReport);
            return $this->sendResponse($result, 'Fund Report saved successfully. Email queued for sending.');
        } catch (Exception $e) {
            report($e);
            return $this->sendError($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // --- inlined from former base ---

    public function index(Request $request)
    {
        $fundReports = $this->fundReportRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(FundReportResource::collection($fundReports), 'Fund Reports retrieved successfully');
    }

    public function show($id)
    {
        /** @var FundReport $fundReport */
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            return $this->sendError('Fund Report not found');
        }

        return $this->sendResponse(new FundReportResource($fundReport), 'Fund Report retrieved successfully');
    }

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
