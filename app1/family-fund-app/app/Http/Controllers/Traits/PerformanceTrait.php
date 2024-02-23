<?php

namespace App\Http\Controllers\Traits;

use App\Models\AssetExt;
use App\Models\Utils;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;

/**
 */
trait PerformanceTrait
{
    protected $perfObject;

    public function createPerformanceArray($start, $asOf, $shares=null, $asset=null)
    {
        // print_r([$this->perfObject->id, $start, $asOf]);
        $yp = array();
        $yp['value']        = Utils::currency($value = $this->perfObject->valueAsOf($asOf));
        $yp['shares']       = Utils::shares($shares = $this->perfObject->sharesAsOf($asOf));
        $yp['share_value']  = Utils::currency($shares > 0 ? $value/$shares : 0);
        $yp['performance']  = Utils::percent($this->perfObject->periodPerformance($start, $asOf));
        return $yp;
    }

    public function createAssetPeformanceArray($start, $asOf, $all_shares, AssetExt $asset)
    {
        $value = 0;
        $assetShares = 0;
        $symbol = $asset->portfolioAssets->first()->symbol;
        try {
            // find asset price with latest timestamp before asOf
            foreach ($all_shares as $shares) {
                if ($shares['timestamp'] <= $asOf) {
                    $assetShares = $shares['shares'];
                } else {
                    break;
                }
            }
            $ap = $asset->pricesAsOf($asOf)->first();
            $value = $assetShares * $ap->price;
        } catch (\Exception $e) {
            Log::error("while calculating $symbol $asOf: " . $e->getMessage());
               Log::error($e->getTraceAsString());
        }
        $yp = array();
        $yp['value']        = Utils::currency($value);
        $yp['shares']       = Utils::shares($assetShares);
        return $yp;
    }

    public function createCashPeformanceArray($start, $asOf, $cash, $asset)
    {
        $value = 0;
        // find cash with latest timestamp before asOf
        foreach ($cash as $timestamp => $value_tmp) {
            if ($timestamp <= $asOf) {
                $value = $value_tmp;
            } else {
                break;
            }
        }
        $yp = array();
        $yp['value'] = Utils::currency($value);
        return $yp;
    }

    public function prepAssetShares($asset, $asOf, $trans)
    {
        $shares = 0;
        $allShares = array();
        $symbol = $asset->portfolioAssets->first()->symbol;
        try {
            foreach ($trans as $tran) {
                if ($tran['timestamp'] <= $asOf) {
                    $ap = $this->asset->pricesAsOf($tran['timestamp'])->first();
                    $shares += $tran['value'] / $ap->price;
                    $allShares[] = [
                        'timestamp' => $tran['timestamp'],
                        'shares' => $shares
                    ];
                    Log::debug($symbol . " value: " . $tran['value'] . " price: $ap->price >> shares: $shares at " . $tran['timestamp']);
                }
            }
        } catch (\Exception $e) {
            Log::error("cant find price for $symbol $asOf: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
        return $allShares;
    }

    public function prepCash($asOf, $trans)
    {
        $allValues = array();
        $total = 0;
        foreach ($trans as $tran) {
            if ($tran['timestamp'] <= $asOf) {
                $timestamp = substr("" . $tran['timestamp'],0,10);
                Log::debug("cash value: " . $tran['value'] . " at " . $timestamp);
                $total += $tran['value'];
                $allValues[$timestamp] = $total;
            }
        }
        return $allValues;
    }

    public function createAssetMonthlyPerformanceResponse($asset, $asOf, $trans) {
        $shares = $this->prepAssetShares($asset, $asOf, $trans);
        return $this->createMonthlyPerformanceResponseFor($asOf, 'createAssetPeformanceArray', $shares, $asset);
    }
//    public function createSP500MonthlyPerformanceResponse($asOf, $trans) {
//        $shares = $this->prepAssetShares(AssetExt::getSP500Asset(), $asOf, $trans);
//        return $this->createMonthlyPerformanceResponseFor($asOf, 'createSP500PeformanceArray', $shares);
//    }

    public function createCashMonthlyPerformanceResponse($asOf, $trans) {
        $shares = $this->prepCash($asOf, $trans);
        return $this->createMonthlyPerformanceResponseFor($asOf, 'createCashPeformanceArray', $shares);
    }

    public function createMonthlyPerformanceResponse($asOf) {
        return $this->createMonthlyPerformanceResponseFor($asOf, 'createPerformanceArray');
    }

    public function createMonthlyPerformanceResponseFor($asOf, $func, $shares=null, $asset=null)
    {
        $arr = array();

        $year = substr($asOf,0,4);
        $month = substr($asOf,5,2);
        $ym = [$year, $month];
        $monthStart = $year.'-'.sprintf("%02d", $month).'-01';

        if ($asOf != $monthStart) {
            $yp = $this->$func($monthStart, $asOf, $shares, $asset);
            $arr[$asOf] = $yp;
        }

        for (; Utils::yearMonthInt($ym) >= 202101; $ym = Utils::decreaseYearMonth($ym)) {
            $monthStart = $ym[0].'-'.sprintf("%02d", $ym[1]).'-01';
            $prevYM = Utils::decreaseYearMonth($ym);
            $prevMonthStart = $prevYM[0].'-'.sprintf("%02d", $prevYM[1]).'-01';
            $yp = $this->$func($prevMonthStart, $monthStart, $shares, $asset);
            $arr[$monthStart] = $yp;
        }

        $ret = $this->removeEmptyStart($arr);
        $ret = array_reverse($ret, true);
        $ret = $this->removeEmptyStart($ret);
        return $ret;
    }

    public function createYearlyPerformanceResponse($asOf)
    {
        $arr = array();

        $year = substr($asOf,0,4);
        $yearStart = $year.'-01-01';

        if ($asOf != $yearStart) {
            $yp = $this->createPerformanceArray($yearStart, $asOf);
            $arr[$asOf] = $yp;
        }

        for (; $year >= 2021; $year--) {
            $yearStart = $year.'-01-01';
            $prevYearStart = ($year-1).'-01-01';
            $yp = $this->createPerformanceArray($prevYearStart, $yearStart);
            $arr[$yearStart] = $yp;
        }

        $ret = $this->removeEmptyStart($arr);
        $ret = array_reverse($ret);
        $ret = $this->removeEmptyStart($ret);
        return $ret;
    }

    /**
     * @param mixed $perfObject
     */
    public function setPerfObject($perfObject): void
    {
        $this->perfObject = $perfObject;
    }

    protected function removeEmptyStart(array $ret): array
    {
        foreach ($ret as $key => $values) {
            // check if key exists
            if (array_key_exists('shares', $values)) {
                if ($values['value'] == 0) {
                    unset($ret[$key]);
                } else {
                    break;
                }
            }
        }
        return $ret;
    }
}
