<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ChangeLog
 * @package App\Models
 * @version July 23, 2022, 12:55 pm UTC
 *
 * @property string $object
 * @property string $content
 */
class ChangeLog extends Model
{
    use HasFactory;

    public $table = 'change_log';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;



    public $fillable = [
        'object',
        'content'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'object' => 'string',
        'content' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'object' => 'required|string|max:50',
        'content' => 'required|string',
        'created_at' => 'nullable'
    ];

    
}
