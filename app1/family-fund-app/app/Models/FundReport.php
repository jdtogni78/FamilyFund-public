<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class FundReport
 * @package App\Models
 * @version February 28, 2022, 6:36 am UTC
 *
 * @property \App\Models\Fund $fund
 * @property integer $fund_id
 * @property string $type
 * @property string $file
 * @property string $start_dt
 * @property string $end_dt
 */
class FundReport extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'fund_reports';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'fund_id',
        'type',
        'file',
        'start_dt',
        'end_dt'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'fund_id' => 'integer',
        'type' => 'string',
        'file' => 'string',
        'start_dt' => 'date',
        'end_dt' => 'date'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'fund_id' => 'required',
        'type' => 'required|string|max:3',
        'file' => 'nullable|string|max:255',
        'start_dt' => 'required',
        'end_dt' => 'required',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function fund()
    {
        return $this->belongsTo(\App\Models\FundExt::class, 'fund_id');
    }
}
