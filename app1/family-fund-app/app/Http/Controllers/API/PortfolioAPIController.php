<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreatePortfolioAPIRequest;
use App\Http\Requests\API\UpdatePortfolioAPIRequest;
use App\Models\Portfolio;
use App\Repositories\PortfolioRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\PortfolioResource;
use Response;

/**
 * Class PortfolioController
 * @package App\Http\Controllers\API
 */

class PortfolioAPIController extends AppBaseController
{
    /** @var  PortfolioRepository */
    protected $portfolioRepository;

    public function __construct(PortfolioRepository $portfolioRepo)
    {
        $this->portfolioRepository = $portfolioRepo;
    }

    /**
     * Display a listing of the Portfolio.
     * GET|HEAD /portfolios
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $portfolios = $this->portfolioRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(PortfolioResource::collection($portfolios), 'Portfolios retrieved successfully');
    }

    /**
     * Store a newly created Portfolio in storage.
     * POST /portfolios
     *
     * @param CreatePortfolioAPIRequest $request
     *
     * @return Response
     */
    public function store(CreatePortfolioAPIRequest $request)
    {
        $input = $request->all();

        $portfolio = $this->portfolioRepository->create($input);

        return $this->sendResponse(new PortfolioResource($portfolio), 'Portfolio saved successfully');
    }

    /**
     * Display the specified Portfolio.
     * GET|HEAD /portfolios/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var Portfolio $portfolio */
        $portfolio = $this->portfolioRepository->find($id);

        if (empty($portfolio)) {
            return $this->sendError('Portfolio not found');
        }

        return $this->sendResponse(new PortfolioResource($portfolio), 'Portfolio retrieved successfully');
    }

    /**
     * Update the specified Portfolio in storage.
     * PUT/PATCH /portfolios/{id}
     *
     * @param int $id
     * @param UpdatePortfolioAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePortfolioAPIRequest $request)
    {
        $input = $request->all();

        /** @var Portfolio $portfolio */
        $portfolio = $this->portfolioRepository->find($id);

        if (empty($portfolio)) {
            return $this->sendError('Portfolio not found');
        }

        $portfolio = $this->portfolioRepository->update($input, $id);

        return $this->sendResponse(new PortfolioResource($portfolio), 'Portfolio updated successfully');
    }

    /**
     * Remove the specified Portfolio from storage.
     * DELETE /portfolios/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var Portfolio $portfolio */
        $portfolio = $this->portfolioRepository->find($id);

        if (empty($portfolio)) {
            return $this->sendError('Portfolio not found');
        }

        $portfolio->delete();

        return $this->sendSuccess('Portfolio deleted successfully');
    }
}
