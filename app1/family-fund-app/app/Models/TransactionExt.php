<?php

namespace App\Models;

use App\Models\Transaction;

/**
 * Class TransactionExt
 * @package App\Models
 */
class TransactionExt extends Transaction
{
    // /**
    //  * Transaction types
    //  * 
    //  * @var array
    //  */
    // public const TYPES = [
    //     1 => 'purchase',
    //     2 => 'sale',
    //     3 => 'borrow',
    //     4 => 'repay'
    // ];

    // /**
    //  * returns the id of a given type
    //  *
    //  * @param string $type  transaction type
    //  * @return int typeID
    //  */
    // public static function getTypeID($type)
    // {
    //     return array_search($type, self::TYPES);
    // }

    // /**
    //  * get transaction type
    //  */
    // public function getTypeAttribute()
    // {
    //     return self::TYPES[ $this->attributes['type'] ];
    // }
    
    // /**
    //  * set transaction type
    //  */
    // public function setTypeAttribute($value)
    // {
    //     $typeID = self::getTypeID($value);
    //     if ($typeID) {
    //        $this->attributes['type_id'] = $typeID;
    //     }
    // }
}
