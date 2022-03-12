<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class AccountMatchingRule
 * @package App\Models
 * @version January 14, 2022, 4:53 am UTC
 *
 * @property \App\Models\Account $account
 * @property \App\Models\MatchingRule $matchingRule
 * @property integer $account_id
 * @property integer $matching_rule_id
 */
class AccountMatchingRule extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'account_matching_rules';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'account_id',
        'matching_rule_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'matching_rule_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'account_id' => 'required',
        'matching_rule_id' => 'required',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function account()
    {
        return $this->belongsTo(\App\Models\AccountExt::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function matchingRule()
    {
        return $this->belongsTo(\App\Models\MatchingRule::class, 'matching_rule_id');
    }
}
