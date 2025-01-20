<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\API\PortfolioAssetAPIController;
use App\Http\Controllers\Traits\BulkStoreTrait;
use App\Http\Requests\API\CreatePortfolioAssetAPIRequest;
use App\Http\Requests\API\CreatePositionUpdateAPIRequest;
use App\Http\Resources\PortfolioAssetResource;
use App\Models\PortfolioAsset;
use App\Models\PortfolioExt;
use App\Repositories\PortfolioAssetRepository;
use DB;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class PortfolioAssetAPIControllerExt extends PortfolioAssetAPIController
{
    use BulkStoreTrait;

    public function __construct(PortfolioAssetRepository $PortfolioAssetsRepo)
    {
        parent::__construct($PortfolioAssetsRepo);
        $this->verbose = false;
    }

    /**
     * Store a newly created PortfolioAssetCollection in storage.
     * POST /PortfolioAssetBulkUpdate
     *
     * @param CreatePositionUpdateAPIRequest $request
     *
     * @return Response
     */
    public function bulkStore(CreatePositionUpdateAPIRequest $request)
    {
        $input = $request->all();
        $source = $input['source'];
        $timestamp = $input['timestamp'];
        $symbols = $request->collect('symbols')->toArray();

        DB::transaction(function () use ($source, $timestamp, $symbols, $request) {
            $this->endDateRemovedAssets($source, $timestamp, $symbols);
            try {
                $this->genericBulkStore($request, 'position');
            } catch (Exception $e) {
                DB::rollback();
                return $this->sendError($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });
        return $this->sendResponse([], 'Bulk position update successful!');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreatePortfolioAssetAPIRequest $request)
    {
        $input = $request->all();

        $PortfolioAsset = $this->insertHistoricalPrice($input['asset_id'], $input['start_dt'], $input['price']);

        return $this->sendResponse(new PortfolioAssetResource($PortfolioAsset), 'Asset Price saved successfully');
    }

    protected function createChild($data, $source)
    {
        $portfolio = PortfolioExt::where('source', $source)->get()->first();
        $data['portfolio_id'] = $portfolio->id;
        $ap = PortfolioAsset::create($data);
        if ($data['position'] != $ap->position) {
            $this->warn("Position was adjusted from ".$data['position']." to ".$ap->position);
        }
        return $ap;
    }

    // wrap getQuery with a better name
    public function getPortfolioAsset($source, $asset, $timestamp)
    {
        return $this->getQuery($source, $asset, $timestamp);
    }

    public function getQuery($source, $asset, $timestamp)
    {
        $portfolio = PortfolioExt::where('source', $source)->get()->first();
        $query = $portfolio->assetsAsOf($timestamp, $asset->id);
        return $query;
    }

    protected function endDateRemovedAssets(mixed $source, mixed $timestamp, array $symbols): void
    {
        $portfolio = PortfolioExt::where('source', $source)->get()->first();
        $pas = $portfolio->assetsAsOf($timestamp);
        foreach ($pas as $pa) {
            $pa2 = array_filter(array_map(function ($n) use ($pa) {
                    $asset = $pa->asset()->first();
                    if ($n['name'] == $asset->name && $n['type'] == $asset->type)
                        return $pa;
                    return null;
                }, $symbols)
            );
            if ($pa2 == null) {
                $pa->end_dt = $timestamp;
                $pa->save();
            }
        }
    }
}
