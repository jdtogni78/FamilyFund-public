<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class SymbolPrice
 * @package App\Models
 * @version March 5, 2022, 8:29 pm UTC
 *
 * @property \App\Models\PriceUpdate $priceUpdate
 * @property string $name
 * @property string $type
 * @property number $price
 */
class SymbolPrice extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'symbol_prices';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'name',
        'type',
        'price'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'type' => 'string',
        'price' => 'float'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:128',
        'type' => 'required|string|max:3',
        'price' => 'required|numeric'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function priceUpdate()
    {
        return $this->belongsTo(\App\Models\PriceUpdate::class);
    }
}
