<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class PriceUpdate
 * @package App\Models
 * @version March 5, 2022, 8:26 pm UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $symbolPrices
 * @property string $source
 * @property string|\Carbon\Carbon $timestamp
 */
class PriceUpdate extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'price_updates';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'source',
        'timestamp'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'source' => 'string',
        'timestamp' => 'datetime'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'source' => 'required|string|max:30',
        'timestamp' => 'required'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function symbolPrices()
    {
        return $this->hasMany(\App\Models\SymbolPrice::class);
    }
}
