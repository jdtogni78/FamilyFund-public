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
 * @property string $data_source
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
        'data_source',
        'name',
        'type',
        'display_group',
        'linked_asset_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'source' => 'string',
        'data_source' => 'string',
        'name' => 'string',
        'type' => 'string',
        'display_group' => 'string',
        'linked_asset_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:128',
        'type' => 'required|regex:/[a-zA-Z][a-zA-Z]+/|max:20',
        'source' => 'nullable|regex:/[a-zA-Z][a-zA-Z]+/|max:50',
        'data_source' => 'required|string|max:30',
        'display_group' => 'nullable|string|max:50',
        'linked_asset_id' => 'nullable|integer|exists:assets,id',
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

    /**
     * The asset this one is linked to (e.g., mortgage linked to property)
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function linkedAsset()
    {
        return $this->belongsTo(\App\Models\Asset::class, 'linked_asset_id');
    }

    /**
     * Assets that link to this one (e.g., mortgages linked to this property)
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function linkedFrom()
    {
        return $this->hasMany(\App\Models\Asset::class, 'linked_asset_id');
    }
}
