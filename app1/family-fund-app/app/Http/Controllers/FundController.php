<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFundRequest;
use App\Http\Requests\CreateFundWithSetupRequest;
use App\Http\Requests\UpdateFundRequest;
use App\Repositories\FundRepository;
use App\Repositories\TransactionRepository;
use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\FundSetupTrait;
use Illuminate\Http\Request;
use Flash;
use Response;

class FundController extends AppBaseController
{
    use FundSetupTrait;

    /** @var  FundRepository */
    protected $fundRepository;

    /** @var  TransactionRepository */
    protected $transactionRepository;

    public function __construct(FundRepository $fundRepo, TransactionRepository $transactionRepo)
    {
        $this->fundRepository = $fundRepo;
        $this->transactionRepository = $transactionRepo;
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

    /**
     * Show the form for creating a new Fund with complete setup
     * (fund + account + portfolio + initial transaction).
     *
     * @return Response
     */
    public function createWithSetup()
    {
        return view('funds.create_with_setup');
    }

    /**
     * Store a newly created Fund with complete setup in storage.
     * Supports preview mode to show what will be created before committing.
     *
     * @param CreateFundWithSetupRequest $request
     *
     * @return Response
     */
    public function storeWithSetup(CreateFundWithSetupRequest $request)
    {
        $input = $request->all();
        $isPreview = $request->input('preview', false);

        try {
            $setupData = $this->setupFund($input, $isPreview);

            if ($isPreview) {
                return view('funds.preview_setup', [
                    'preview' => $setupData,
                    'input' => $input,
                ]);
            } else {
                Flash::success('Fund created successfully with account, portfolio, and initial transaction!');
                return redirect(route('funds.show', $setupData['fund']->id));
            }
        } catch (\Exception $e) {
            Flash::error('Fund creation failed: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
}
