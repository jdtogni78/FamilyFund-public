<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateMatchingRuleRequest;
use App\Http\Requests\UpdateMatchingRuleRequest;
use App\Repositories\MatchingRuleRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class MatchingRuleController extends AppBaseController
{
    /** @var  MatchingRuleRepository */
    protected $matchingRuleRepository;

    public function __construct(MatchingRuleRepository $matchingRuleRepo)
    {
        $this->matchingRuleRepository = $matchingRuleRepo;
    }

    /**
     * Display a listing of the MatchingRule.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $matchingRules = $this->matchingRuleRepository->all();

        return view('matching_rules.index')
            ->with('matchingRules', $matchingRules);
    }

    /**
     * Show the form for creating a new MatchingRule.
     *
     * @return Response
     */
    public function create()
    {
        return view('matching_rules.create');
    }

    /**
     * Store a newly created MatchingRule in storage.
     *
     * @param CreateMatchingRuleRequest $request
     *
     * @return Response
     */
    public function store(CreateMatchingRuleRequest $request)
    {
        $input = $request->all();

        $matchingRule = $this->matchingRuleRepository->create($input);

        Flash::success('Matching Rule saved successfully.');

        return redirect(route('matchingRules.index'));
    }

    /**
     * Display the specified MatchingRule.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $matchingRule = $this->matchingRuleRepository->find($id);

        if (empty($matchingRule)) {
            Flash::error('Matching Rule not found');

            return redirect(route('matchingRules.index'));
        }

        return view('matching_rules.show')->with('matchingRule', $matchingRule);
    }

    /**
     * Show the form for editing the specified MatchingRule.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $matchingRule = $this->matchingRuleRepository->find($id);

        if (empty($matchingRule)) {
            Flash::error('Matching Rule not found');

            return redirect(route('matchingRules.index'));
        }

        return view('matching_rules.edit')->with('matchingRule', $matchingRule);
    }

    /**
     * Update the specified MatchingRule in storage.
     *
     * @param int $id
     * @param UpdateMatchingRuleRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateMatchingRuleRequest $request)
    {
        $matchingRule = $this->matchingRuleRepository->find($id);

        if (empty($matchingRule)) {
            Flash::error('Matching Rule not found');

            return redirect(route('matchingRules.index'));
        }

        $matchingRule = $this->matchingRuleRepository->update($request->all(), $id);

        Flash::success('Matching Rule updated successfully.');

        return redirect(route('matchingRules.index'));
    }

    /**
     * Remove the specified MatchingRule from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $matchingRule = $this->matchingRuleRepository->find($id);

        if (empty($matchingRule)) {
            Flash::error('Matching Rule not found');

            return redirect(route('matchingRules.index'));
        }

        $this->matchingRuleRepository->delete($id);

        Flash::success('Matching Rule deleted successfully.');

        return redirect(route('matchingRules.index'));
    }
}
