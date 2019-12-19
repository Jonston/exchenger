<?php

namespace App\Libraries;

use App\Exceptions\CannotApproveApprovedRequestException;
use App\Exceptions\CannotDeleteApprovedRequestExceprion;
use App\Exceptions\CannotFindRequestException;
use App\Exceptions\InvalidCurrencyException;
use App\Exceptions\InvalidOperationException;
use App\Exceptions\InvalidRateException;
use App\Exceptions\NotEnoughFundsException;
use App\User;
use App\ExchangeRequest as ExchangeModel;

class ExchangeRequest{

    protected $request;

    protected $currencies = [
        ExchangeModel::CURRENCY_STB,
        ExchangeModel::CURRENCY_GNR
    ];

    protected $operations = [
        ExchangeModel::OPERATION_PURCHASE,
        ExchangeModel::OPERATION_SALE
    ];

    /**
     * ExchangeRequest constructor.
     * @param ExchangeModel $request
     */
    protected function __construct(ExchangeModel $request = null)
    {
        $this->request = $request ? $request : new ExchangeModel();
    }

    /**
     * @param User $userFrom
     * @param string $operation
     * @param string $currency
     * @param float $amount
     * @param float $rate
     * @return static
     * @throws NotEnoughFundsException
     */
    public static function create(User $userFrom, string $operation, string $currency, float $amount, float $rate)
    {
        $instance = new static();

        if( ! $instance->isValidOperation($operation))
            throw new InvalidOperationException("Wrong operations.Allowed operations: " . join(', ', $instance->operations));

        if( ! $instance->isValidCurrency($currency))
            throw new InvalidCurrencyException("Wrong currency.Allowed currencies: " . join(', ', $instance->currencies));

        if($rate <= 0)
            throw new InvalidRateException("Rate must be an positive number");

        $instance->request->fill([
            'user_from' => $userFrom->id,
            'currency'  => $currency,
            'operation' => $operation,
            'amount'    => $amount,
            'rate'      => $rate
        ]);

        $instance->reserveSum($userFrom, $operation, $currency, $amount, $rate);

        $instance->request->save();

        return $instance;
    }

    /**
     * @param int $id
     * @return static
     * @throws CannotFindRequestException
     */
    public static function find(int $id)
    {
        $request = ExchangeModel::find($id);

        if( ! $request)
            throw new CannotFindRequestException("Exchange request #$id not found");

        return new static($request);
    }

    /**
     * @param User $userTo
     * @return $this
     * @throws CannotApproveApprovedRequestException
     * @throws NotEnoughFundsException
     */
    public function approve(User $userTo)
    {
        if($this->isApproved())
            throw new CannotApproveApprovedRequestException('The request has already approved');

        $this->reserveSum(
            $userTo,
            $this->invertOperation($this->request->operation),
            $this->request->currency,
            $this->request->amount,
            $this->request->rate
        );

        $this->request->userTo()->associate($userTo);
        $this->request->save();

        $userFrom = $this->request->userFrom;
        $operation = $this->request->operation;
        $currency = $this->request->currency;
        $amount = $this->request->amount;
        $rate = $this->request->rate;

        if($operation === ExchangeModel::OPERATION_PURCHASE){
            $userTo->addSum($this->invertCurrency($currency), $amount * $rate);
            $userFrom->addSum($currency, $amount);
        }else{
            $userFrom->addSum($this->invertCurrency($currency), $amount * $rate);
            $userTo->addSum($currency, $amount);
        }

        return $this;
    }

    public function delete()
    {
        if($this->request->isApproved())
            throw new CannotDeleteApprovedRequestExceprion("Cannot delete approved request");

        $userFrom = $this->request->userFrom;
        $operation = $this->request->operation;
        $currency = $this->request->currency;
        $amount = $this->request->amount;
        $rate = $this->request->rate;

        $this->reserveSum($userFrom, $operation, $currency, -$amount, $rate);

        $this->request->delete();

        return $this;
    }

    protected  function isApproved()
    {
        return $this->request->userFrom && $this->request->userTo;
    }

    /**
     * @param string $operation
     * @return bool
     */
    protected function isValidOperation(string $operation)
    {
        return in_array($operation, $this->operations);
    }

    /**
     * @param string $currency
     * @return bool
     */
    protected function isValidCurrency(string $currency)
    {
        return in_array($currency, $this->currencies);
    }

    /**
     * @param string $currency
     * @return string
     */
    protected function invertCurrency(string $currency)
    {
        return $currency === ExchangeModel::CURRENCY_GNR ?
            ExchangeModel::CURRENCY_STB :
            ExchangeModel::CURRENCY_GNR;
    }

    /**
     * @param string $currency
     * @return string
     */
    protected function invertOperation(string $currency)
    {
        return $currency === ExchangeModel::OPERATION_PURCHASE
            ? ExchangeModel::OPERATION_SALE
            : ExchangeModel::OPERATION_PURCHASE;
    }

    /**
     * @param User $user
     * @param string $operation
     * @param string $currency
     * @param float $amount
     * @param float $rate
     * @return bool
     * @throws NotEnoughFundsException
     */
    protected function reserveSum(User $user, string $operation, string $currency, float $amount, float $rate)
    {
        if($operation === ExchangeModel::OPERATION_PURCHASE){
            $amount = $amount * $rate;
            $currency = $this->invertCurrency($currency);
        }

        if( ! $user->hasSum($currency, $amount))
            throw new NotEnoughFundsException("Not enough funds");

        $user->$currency -= $amount;
        return $user->save();
    }

    /**
     * @param string $name
     * @param $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $allowedMethods = [
            'toArray', 'userFrom', 'UserTo'
        ];

        if( ! in_array($name, $allowedMethods))
            throw new NotAllowedMethodException("Method: $name is not allowed.");

        return call_user_func_array([$this->request, $name], $arguments);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->request->$name;
    }

    /**
     * @return ExchangeModel
     */
    public function model()
    {
        return $this->request;
    }

}
