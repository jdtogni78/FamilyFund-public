<?php

namespace App\Http\Controllers\Traits;

use App\Models\Utils;

trait PerformanceTrait
{
    protected $perfObject;

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

    public function createMonthlyPerformanceResponse($asOf)
    {
        $arr = array();

        $year = substr($asOf,0,4);
        $month = substr($asOf,5,2);
        $ym = [$year, $month];
        $monthStart = $year.'-'.sprintf("%02d", $month).'-01';

        if ($asOf != $monthStart) {
            $yp = $this->createPeformanceArray($monthStart, $asOf);
            $arr[$asOf] = $yp;
        }

        for (; Utils::yearMonthInt($ym) >= 202101; $ym = Utils::decreaseYearMonth($ym)) {
            $monthStart = $ym[0].'-'.sprintf("%02d", $ym[1]).'-01';
            $prevYM = Utils::decreaseYearMonth($ym);
            $prevMonthStart = $prevYM[0].'-'.sprintf("%02d", $prevYM[1]).'-01';
            $yp = $this->createPeformanceArray($prevMonthStart, $monthStart);
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
