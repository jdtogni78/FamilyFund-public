<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Asset
 * @package App\Models
 * @version February 23, 2024, 9:34 am UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $assetChangeLogs
 * @property \Illuminate\Database\Eloquent\Collection $assetPrices
 * @property \Illuminate\Database\Eloquent\Collection $portfolioAssets
 * @property string $source
 * @property string $name
 * @property string $type
 * @property string $display_group
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
        'source',
        'name',
        'type',
        'display_group'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'source' => 'string',
        'name' => 'string',
        'type' => 'string',
        'display_group' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:128',
        'type' => 'required|regex:/[a-zA-Z][a-zA-Z]+/|max:20',
        'source' => 'required|regex:/[a-zA-Z][a-zA-Z]+/|max:50',
        'display_group' => 'nullable|string|max:50',
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
