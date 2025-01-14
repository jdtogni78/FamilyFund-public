<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class CashDeposit
 * @package App\Models
 * @version January 14, 2025, 4:21 am UTC
 *
 * @property string $date
 * @property string $description
 * @property number $value
 */
class CashDeposit extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'cash_deposits';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'date',
        'description',
        'value'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'date' => 'date',
        'description' => 'string',
        'value' => 'decimal:2'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date' => 'required',
        'description' => 'nullable',
        'value' => 'required|numeric|min:0'
    ];

    
}
