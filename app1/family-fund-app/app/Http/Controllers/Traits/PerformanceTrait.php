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
    protected $sp500Trans;
    private AssetExt $sp500Asset;
    private array $sp500Shares;

    public function createPeformanceArray($start, $asOf)
    {
        // print_r([$this->perfObject->id, $start, $asOf]);
        $yp = array();
        $yp['value']        = Utils::currency($value = $this->perfObject->valueAsOf($asOf));
        $yp['shares']       = Utils::shares($shares = $this->perfObject->sharesAsOf($asOf));
        $yp['share_value']  = Utils::currency($shares > 0 ? $value/$shares : 0);
        $yp['performance']  = Utils::percent($this->perfObject->periodPerformance($start, $asOf));
        return $yp;
    }

    public function prepSP500Shares($asOf)
    {
        $shares = 0;
        $allShares = array();
        try {
            $this->sp500Asset = AssetExt::getSP500Asset();
            foreach ($this->sp500Trans as $trans) {
                if ($trans['timestamp'] <= $asOf) {
                    $sp500Price = $this->sp500Asset->pricesAsOf($trans['timestamp'])->first();
                    $shares += $trans['value'] / $sp500Price->price;
                    $allShares[] = [
                        'timestamp' => $trans['timestamp'],
                        'shares' => $shares
                    ];
                    Log::debug("sp500 value: " . $trans['value'] . " price: $sp500Price->price >> shares: $shares at " . $trans['timestamp']);
                }
            }
        } catch (\Exception $e) {
            Log::error("cant find price for sp500 $asOf: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
        return $allShares;
    }

    public function createSP500PeformanceArray($start, $asOf)
    {
        $value = 0;
        $sp500Shares = 0;
        try {
            // find sp500Shares with latest timestamp before asOf
            foreach ($this->sp500Shares as $shares) {
                if ($shares['timestamp'] <= $asOf) {
                    $sp500Shares = $shares['shares'];
                } else {
                    break;
                }
            }
            $sp500Price = $this->sp500Asset->pricesAsOf($asOf)->first();
            $value = $sp500Shares * $sp500Price->price;
        } catch (\Exception $e) {
            Log::error("while calculating sp500 $asOf: " . $e->getMessage());
               Log::error($e->getTraceAsString());
        }
        $yp = array();
        $yp['value']        = Utils::currency($value);
        $yp['shares']       = Utils::shares($sp500Shares);
        return $yp;
    }

    public function createSP500MonthlyPerformanceResponse($asOf, $trans)
    {
        $this->sp500Trans = $trans;
        $this->sp500Shares = $this->prepSP500Shares($asOf);
        return $this->createMonthlyPerformanceResponseFor($asOf, 'createSP500PeformanceArray');
    }

    public function createMonthlyPerformanceResponse($asOf)
    {
        return $this->createMonthlyPerformanceResponseFor($asOf, 'createPeformanceArray');
    }

    public function createMonthlyPerformanceResponseFor($asOf, $func)
    {
        $arr = array();

        $year = substr($asOf,0,4);
        $month = substr($asOf,5,2);
        $ym = [$year, $month];
        $monthStart = $year.'-'.sprintf("%02d", $month).'-01';

        if ($asOf != $monthStart) {
            $yp = $this->$func($monthStart, $asOf);
            $arr[$asOf] = $yp;
        }

        for (; Utils::yearMonthInt($ym) >= 202101; $ym = Utils::decreaseYearMonth($ym)) {
            $monthStart = $ym[0].'-'.sprintf("%02d", $ym[1]).'-01';
            $prevYM = Utils::decreaseYearMonth($ym);
            $prevMonthStart = $prevYM[0].'-'.sprintf("%02d", $prevYM[1]).'-01';
            $yp = $this->$func($prevMonthStart, $monthStart);
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
            $yp = $this->createPeformanceArray($yearStart, $asOf);
            $arr[$asOf] = $yp;
        }

        for (; $year >= 2021; $year--) {
            $yearStart = $year.'-01-01';
            $prevYearStart = ($year-1).'-01-01';
            $yp = $this->createPeformanceArray($prevYearStart, $yearStart);
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
            if ($values['shares'] == 0) {
                unset($ret[$key]);
            } else {
                break;
            }
        }
        return $ret;
    }
}
