<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExchangeRequest extends Model{
    const OPERATION_PURCHASE = 'purchase';

    const OPERATION_SALE = 'sale';

    const CURRENCY_STB = 'stb';

    const CURRENCY_GNR = 'gnr';

    protected $fillable = [
        'user_from',
        'user_to',
        'currency',
        'operation',
        'amount',
        'rate'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userFrom()
    {
        return $this->belongsTo(User::class, 'user_from');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userTo()
    {
        return $this->belongsTo(User::class, 'user_to');
    }

    /**
     * @return \Illuminate\Database\Query\Builder;
     */
    public function pending()
    {
        return $this->where('user_to', null);
    }

    /**
     * @return \Illuminate\Database\Query\Builder;
     */
    public function approved()
    {
        return $this->whereNotNull('user_to');
    }

    /**
     * @return bool
     */
    public function isApproved()
    {
        return $this->userFrom && $this->userTo;
    }

}
