<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class PortfolioAsset
 * @package App\Models
 * @version January 14, 2022, 4:54 am UTC
 *
 * @property \App\Models\Asset $asset
 * @property \App\Models\Portfolio $portfolio
 * @property integer $portfolio_id
 * @property integer $asset_id
 * @property number $position
 * @property string $start_dt
 * @property string $end_dt
 */
class PortfolioAsset extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'portfolio_assets';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'portfolio_id',
        'asset_id',
        'position',
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
        'portfolio_id' => 'integer',
        'asset_id' => 'integer',
        'position' => 'decimal:8',
        'start_dt' => 'date',
        'end_dt' => 'date'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'portfolio_id' => 'required',
        'asset_id' => 'required',
        'position' => 'required|numeric',
        'start_dt' => 'required',
        'end_dt' => 'required',
        'updated_at' => 'nullable',
        'created_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function asset()
    {
        return $this->belongsTo(\App\Models\AssetExt::class, 'asset_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function portfolio()
    {
        return $this->belongsTo(\App\Models\PortfolioExt::class, 'portfolio_id');
    }
}
