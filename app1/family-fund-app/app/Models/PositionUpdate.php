<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class PositionUpdate
 * @package App\Models
 * @version March 7, 2022, 3:07 am UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $symbolPositions
 * @property string $source
 * @property string|\Carbon\Carbon $timestamp
 */
class PositionUpdate extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'position_updates';
    

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
    public function symbolPositions()
    {
        return $this->hasMany(\App\Models\SymbolPosition::class);
    }
}
