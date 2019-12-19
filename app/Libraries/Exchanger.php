<?php

namespace App\Libraries;

use App\Exceptions\CannotDeleteApprovedRequestExceprion;
use App\Exceptions\IsOwnExchangeRequestException;
use App\ExchangeRequest;
use App\User;
use App\Exceptions\NotEnoughFundsException;

class Exchanger{

    /**
     * @param User $user
     * @param string $operation
     * @param string $currency
     * @param float $amount
     * @param float $rate
     * @return mixed
     * @throws NotEnoughFundsException
     */
    protected static function create(User $user, string $operation, string $currency, float $amount, float $rate)
    {
        if($operation === ExchangeRequest::OPERATION_PURCHASE){
            self::hasSum($user, self::invertCurrency($currency), $amount * $rate);
        }elseif($operation === ExchangeRequest::OPERATION_SALE){
            self::hasSum($user, $currency, $amount);
        }

        $request = ExchangeRequest::create([
            'user_from' => $user->id,
            'operation' => $operation,
            'currency'  => $currency,
            'amount'    => $amount,
            'rate'      => $rate
        ]);

        if($operation === ExchangeRequest::OPERATION_PURCHASE){
            self::subSum($user, self::invertCurrency($currency), $amount * $rate);
        }elseif($operation === ExchangeRequest::OPERATION_SALE){
            self::subSum($user, $currency, $amount);
        }

        return $request;
    }

    /**
     * @param ExchangeRequest $request
     * @throws CannotDeleteApprovedRequestExceprion
     */
    public static function deleteRequest(ExchangeRequest $request)
    {
        if(self::isApproved($request))
            throw new CannotDeleteApprovedRequestExceprion("Request #$request->id is already approved");

        $userFrom = $request->userFrom;
        $currency = $request->currency;
        $amount = $request->amount;
        $rate = $request->rate;

        if($request->operation === ExchangeRequest::OPERATION_PURCHASE){
            self::addSum($userFrom, self::invertCurrency($currency), $amount * $rate);
        }elseif($request->operation === ExchangeRequest::OPERATION_SALE){
            self::addSum($userFrom, $currency, $amount);
        }

        $request->delete();
    }

    /**
     * @param User $user
     * @param string $currency
     * @param float $amount
     * @param float $rate
     * @return mixed
     * @throws NotEnoughFundsException
     */
    public static function purchaseRequest(User $user, string $currency, float $amount, float $rate)
    {
        return self::create(
            $user,
            ExchangeRequest::OPERATION_PURCHASE,
            $currency,
            $amount,
            $rate
        );
    }

    /**
     * @param User $user
     * @param string $currency
     * @param float $amount
     * @param float $rate
     * @return mixed
     * @throws NotEnoughFundsException
     */
    public static function saleRequest(User $user, string $currency, float $amount, float $rate)
    {
        return self::create(
            $user,
            ExchangeRequest::OPERATION_SALE,
            $currency,
            $amount,
            $rate
        );
    }

    /**
     * @param ExchangeRequest $request
     * @param User $userTo
     * @return ExchangeRequest
     * @throws IsOwnExchangeRequestException
     * @throws NotEnoughFundsException
     */
    public static function approve(ExchangeRequest $request, User $userTo)
    {
        if(self::isOwnRequest($request, $userTo))
            throw new IsOwnExchangeRequestException("User #$userTo->id is owner request #$request->id");


        $request->user_to = $userTo->id;
        $request->save();

        $userFrom = $request->userFrom;
        $currency = $request->currency;
        $operation = $request->operation;
        $amount = $request->amount;
        $rate = $request->rate;

        if($operation === ExchangeRequest::OPERATION_PURCHASE){
            self::hasSum($userTo, $currency, $amount);
            self::addSum($userFrom, $currency, $amount);
            self::subSum($userTo, $currency, $amount);
            self::addSum($userTo, self::invertCurrency($currency), $amount * $rate);
        }elseif($operation === ExchangeRequest::OPERATION_SALE){
            self::hasSum($userTo, self::invertCurrency($currency), $amount * $rate);
            self::addSum($userFrom, self::invertCurrency($currency), $amount * $rate);
            self::subSum($userTo, self::invertCurrency($currency), $amount * $rate);
            self::addSum($userTo, $currency, $amount);
        }

        return $request;
    }

    /**
     * @param ExchangeRequest $request
     * @return bool
     */
    protected static function isApproved(ExchangeRequest $request)
    {
        return $request->userFrom && $request->userTo;
    }

    /**
     * @param User $user
     * @param string $currency
     * @param string $amount
     * @return bool
     * @throws NotEnoughFundsException
     */
    protected static function hasSum(User $user, string $currency, string $amount)
    {
        if($user->$currency - $amount < 0)
            throw new NotEnoughFundsException("User #$user->id has not enough funds");

        return true;
    }

    protected static function subSum(User $user, string $currency, float $amount)
    {
        $user->$currency -= $amount;
        $user->save();
    }

    /**
     * @param User $user
     * @param string $currency
     * @param float $amount
     */
    protected static function addSum(User $user, string $currency, float $amount)
    {
        $user->$currency += $amount;
        $user->save();
    }

    /**
     * @param string $currency
     * @return string
     */
    protected static function invertCurrency(string $currency)
    {
        return $currency === 'stb' ? 'gnr' : 'stb';
    }

    /**
     * @param ExchangeRequest $request
     * @param User $user
     * @return bool
     */
    public static function isOwnRequest(ExchangeRequest $request, User $user)
    {
        return $request->user_from === $user->id;
    }

    /**
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    public static function pendingRequests(User $user)
    {
        return $user->pendingExchangeRequests()->get();
    }

    /**
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    public static function approvedRequests(User $user)
    {
        return $user->approvedExchangeRequests()->get();
    }

    /**
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    public static function allRequests(User $user)
    {
        return $user->exchangeRequests()->get();
    }
}
