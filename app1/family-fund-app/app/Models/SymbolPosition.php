<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class SymbolPosition
 * @package App\Models
 * @version March 7, 2022, 3:07 am UTC
 *
 * @property \App\Models\PositionUpdate $positionUpdate
 * @property string $name
 * @property string $type
 * @property number $position
 */
class SymbolPosition extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'symbol_positions';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'name',
        'type',
        'position'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'type' => 'string',
        'position' => 'float'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:128',
        'type' => 'required|string|max:3',
        'position' => 'required|numeric'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function positionUpdate()
    {
        return $this->belongsTo(\App\Models\PositionUpdate::class);
    }
}
