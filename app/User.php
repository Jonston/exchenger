<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'gnr', 'stb'
    ];

    /**
     * @param string $currency
     * @param string $amount
     * @return bool
     */
    public function hasSum(string $currency, string $amount)
    {
        return $this->$currency >= $amount;
    }

    /**
     * @param string $currency
     * @param string $amount
     * @return bool
     */
    public function addSum(string $currency, string $amount)
    {
        $this->$currency += $amount;
        return $this->save();
    }
    /**
     * @param string $currency
     * @param string $amount
     * @return bool
     */
    public function subtractSum(string $currency, string $amount)
    {
        $this->$currency -= $amount;
        return $this->save();
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exchangeRequests()
    {
        return $this->hasMany(ExchangeRequest::class, 'user_from');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function pendingExchangeRequests()
    {
        return $this->exchangeRequests()->where('user_to', null);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function approvedExchangeRequests()
    {
        return $this->exchangeRequests()->whereNotNull('user_to');
    }

}
