<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class FundReport
 * @package App\Models
 * @version March 28, 2022, 2:48 am UTC
 *
 * @property \App\Models\Fund $fund
 * @property integer $fund_id
 * @property string $type
 * @property string $as_of
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
        'as_of'
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
        'as_of' => 'date'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'fund_id' => 'required',
        'type' => 'required|string|max:3',
        'as_of' => 'required',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function fund()
    {
        return $this->belongsTo(\App\Models\Fund::class, 'fund_id');
    }
}
