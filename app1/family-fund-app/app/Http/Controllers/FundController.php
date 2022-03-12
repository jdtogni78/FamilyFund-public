<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFundRequest;
use App\Http\Requests\UpdateFundRequest;
use App\Repositories\FundRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class FundController extends AppBaseController
{
    /** @var  FundRepository */
    protected $fundRepository;

    public function __construct(FundRepository $fundRepo)
    {
        $this->fundRepository = $fundRepo;
    }

    /**
     * Display a listing of the Fund.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $funds = $this->fundRepository->all();

        return view('funds.index')
            ->with('funds', $funds);
    }

    /**
     * Show the form for creating a new Fund.
     *
     * @return Response
     */
    public function create()
    {
        return view('funds.create');
    }

    /**
     * Store a newly created Fund in storage.
     *
     * @param CreateFundRequest $request
     *
     * @return Response
     */
    public function store(CreateFundRequest $request)
    {
        $input = $request->all();

        $fund = $this->fundRepository->create($input);

        Flash::success('Fund saved successfully.');

        return redirect(route('funds.index'));
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
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');

            return redirect(route('funds.index'));
        }

        return view('funds.show')->with('fund', $fund);
    }

    /**
     * Show the form for editing the specified Fund.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');

            return redirect(route('funds.index'));
        }

        return view('funds.edit')->with('fund', $fund);
    }

    /**
     * Update the specified Fund in storage.
     *
     * @param int $id
     * @param UpdateFundRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateFundRequest $request)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');

            return redirect(route('funds.index'));
        }

        $fund = $this->fundRepository->update($request->all(), $id);

        Flash::success('Fund updated successfully.');

        return redirect(route('funds.index'));
    }

    /**
     * Remove the specified Fund from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');

            return redirect(route('funds.index'));
        }

        $this->fundRepository->delete($id);

        Flash::success('Fund deleted successfully.');

        return redirect(route('funds.index'));
    }
}
