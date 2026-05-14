<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class TransactionReversal
 * @package App\Models
 *
 * Immutable audit row recording a reversed BOR/REP transaction.
 *
 * @property int $id
 * @property int $transaction_id
 * @property int|null $original_target_credit_line_id
 * @property string $reversed_at
 * @property int $reversed_by_user_id
 * @property string $reason
 */
class TransactionReversal extends Model
{
    use HasFactory;

    public $table = 'transaction_reversals';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = ['reversed_at'];

    public $fillable = [
        'transaction_id',
        'original_target_credit_line_id',
        'reversed_at',
        'reversed_by_user_id',
        'reason',
    ];

    protected $casts = [
        'id' => 'integer',
        'transaction_id' => 'integer',
        'original_target_credit_line_id' => 'integer',
        'reversed_at' => 'datetime',
        'reversed_by_user_id' => 'integer',
        'reason' => 'string',
    ];

    public static $rules = [
        'transaction_id' => 'required',
        'reversed_at' => 'required|date',
        'reversed_by_user_id' => 'required',
        'reason' => 'required|string|max:1024',
    ];

    public function transaction()
    {
        return $this->belongsTo(\App\Models\TransactionExt::class, 'transaction_id');
    }

    public function originalTargetCreditLine()
    {
        return $this->belongsTo(\App\Models\AccountCreditLine::class, 'original_target_credit_line_id');
    }

    public function reversedBy()
    {
        return $this->belongsTo(\App\Models\UserExt::class, 'reversed_by_user_id');
    }
}
