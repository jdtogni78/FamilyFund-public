<?php

namespace App\Repositories;

use App\Models\PortfolioReportExt;
use App\Repositories\BaseRepository;

/**
 * Class PortfolioReportRepository
 * @package App\Repositories
 */
class PortfolioReportRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'portfolio_id',
        'start_date',
        'end_date',
        'scheduled_job_id'
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     */
    public function model()
    {
        return PortfolioReportExt::class;
    }
}
