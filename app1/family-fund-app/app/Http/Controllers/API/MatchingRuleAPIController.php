<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateMatchingRuleAPIRequest;
use App\Http\Requests\API\UpdateMatchingRuleAPIRequest;
use App\Models\MatchingRule;
use App\Repositories\MatchingRuleRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\MatchingRuleResource;
use Response;

/**
 * Class MatchingRuleController
 * @package App\Http\Controllers\API
 */

class MatchingRuleAPIController extends AppBaseController
{
    /** @var  MatchingRuleRepository */
    protected $matchingRuleRepository;

    public function __construct(MatchingRuleRepository $matchingRuleRepo)
    {
        $this->matchingRuleRepository = $matchingRuleRepo;
    }

    /**
     * Display a listing of the MatchingRule.
     * GET|HEAD /matchingRules
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $matchingRules = $this->matchingRuleRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(MatchingRuleResource::collection($matchingRules), 'Matching Rules retrieved successfully');
    }

    /**
     * Store a newly created MatchingRule in storage.
     * POST /matchingRules
     *
     * @param CreateMatchingRuleAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateMatchingRuleAPIRequest $request)
    {
        $input = $request->all();

        $matchingRule = $this->matchingRuleRepository->create($input);

        return $this->sendResponse(new MatchingRuleResource($matchingRule), 'Matching Rule saved successfully');
    }

    /**
     * Display the specified MatchingRule.
     * GET|HEAD /matchingRules/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var MatchingRule $matchingRule */
        $matchingRule = $this->matchingRuleRepository->find($id);

        if (empty($matchingRule)) {
            return $this->sendError('Matching Rule not found');
        }

        return $this->sendResponse(new MatchingRuleResource($matchingRule), 'Matching Rule retrieved successfully');
    }

    /**
     * Update the specified MatchingRule in storage.
     * PUT/PATCH /matchingRules/{id}
     *
     * @param int $id
     * @param UpdateMatchingRuleAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateMatchingRuleAPIRequest $request)
    {
        $input = $request->all();

        /** @var MatchingRule $matchingRule */
        $matchingRule = $this->matchingRuleRepository->find($id);

        if (empty($matchingRule)) {
            return $this->sendError('Matching Rule not found');
        }

        $matchingRule = $this->matchingRuleRepository->update($input, $id);

        return $this->sendResponse(new MatchingRuleResource($matchingRule), 'MatchingRule updated successfully');
    }

    /**
     * Remove the specified MatchingRule from storage.
     * DELETE /matchingRules/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var MatchingRule $matchingRule */
        $matchingRule = $this->matchingRuleRepository->find($id);

        if (empty($matchingRule)) {
            return $this->sendError('Matching Rule not found');
        }

        $matchingRule->delete();

        return $this->sendSuccess('Matching Rule deleted successfully');
    }
}
