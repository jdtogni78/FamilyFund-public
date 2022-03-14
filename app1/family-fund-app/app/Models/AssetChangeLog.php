<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class AssetChangeLog
 * @package App\Models
 * @version January 14, 2022, 4:54 am UTC
 *
 * @property \App\Models\Asset $asset
 * @property string $action
 * @property integer $asset_id
 * @property string $field
 * @property string $content
 * @property string|\Carbon\Carbon $datetime
 */
class AssetChangeLog extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'asset_change_logs';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'action',
        'asset_id',
        'field',
        'content',
        'datetime'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'action' => 'string',
        'asset_id' => 'integer',
        'field' => 'string',
        'content' => 'string',
        'datetime' => 'datetime'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'action' => 'required|string|max:255',
        'asset_id' => 'required',
        'field' => 'required|string',
        'content' => 'required|string',
        'datetime' => 'required',
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
}
