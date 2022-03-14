<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Asset
 * @package App\Models
 * @version March 5, 2022, 9:25 pm UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $assetChangeLogs
 * @property \Illuminate\Database\Eloquent\Collection $assetPrices
 * @property \Illuminate\Database\Eloquent\Collection $portfolioAssets
 * @property string $name
 * @property string $type
 * @property string $source
 */
class Asset extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'assets';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'name',
        'type',
        'source'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'type' => 'string',
        'source' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:128',
        'type' => 'required|[a-zA-Z][a-zA-Z]+|max:20',
        'source' => 'required|[a-zA-Z][a-zA-Z]+|max:50',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function assetChangeLogs()
    {
        return $this->hasMany(\App\Models\AssetChangeLog::class, 'asset_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function assetPrices()
    {
        return $this->hasMany(\App\Models\AssetPrice::class, 'asset_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function portfolioAssets()
    {
        return $this->hasMany(\App\Models\PortfolioAsset::class, 'asset_id');
    }
}
