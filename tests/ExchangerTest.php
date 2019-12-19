<?php

use App\Exceptions\CannotDeleteApprovedRequestExceprion;
use App\Exceptions\IsOwnExchangeRequestException;
use App\Exceptions\NotEnoughFundsException;
use App\ExchangeRequest;
use App\Libraries\Exchanger;
use App\User;
use Illuminate\Support\Facades\DB;

class ExchangerTest extends TestCase
{
    public function testCreatePurchaseRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $request = Exchanger::purchaseRequest(User::find(1), 'stb', 10, .5);
        $userFrom = $request->userFrom;

        $this->assertEquals([
            'currency'  => 'stb',
            'amount'    => 10,
            'operation' => ExchangeRequest::OPERATION_PURCHASE,
            'rate'      => .5
        ], collect($request->toArray())->only('currency', 'amount', 'operation', 'rate')->all());
        $this->assertEquals($userFrom->gnr, 95);
    }

    public function testApprovePurchaseRequest()
    {
        $userTo = User::find(2);
        $request = Exchanger::approve(ExchangeRequest::find(1), $userTo);

        $this->assertEquals($request->userTo->id, $userTo->id);
        $this->assertEquals([
            'stb' => 110,
            'gnr' => 95
        ], collect($request->userFrom->toArray())->only('stb', 'gnr')->all());
        $this->assertEquals([
            'stb' => 90,
            'gnr' => 105
        ], collect($request->userTo->toArray())->only('stb', 'gnr')->all());
    }

    public function testCreateSaleRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $request = Exchanger::saleRequest(User::find(1), 'stb', 10, .5);
        $userFrom = $request->userFrom;

        $this->assertEquals([
            'currency'  => 'stb',
            'amount'    => 10,
            'operation' => ExchangeRequest::OPERATION_SALE,
            'rate'      => .5
        ], collect($request->toArray())->only('currency', 'amount', 'operation', 'rate')->all());
        $this->assertEquals($userFrom->stb, 90);
    }

    public function testApproveSaleRequest()
    {
        $userTo = User::find(2);
        $request = Exchanger::approve(ExchangeRequest::find(1), $userTo);

        $this->assertEquals($request->userTo->id, $userTo->id);
        $this->assertEquals([
            'stb' => 90,
            'gnr' => 105
        ], collect($request->userFrom->toArray())->only('stb', 'gnr')->all());
        $this->assertEquals([
            'stb' => 110,
            'gnr' => 95
        ], collect($request->userTo->toArray())->only('stb', 'gnr')->all());
    }

    public function testCannotCreateRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $this->expectException(NotEnoughFundsException::class);

        Exchanger::purchaseRequest(User::find(1), 'stb', 100, 1.5);
    }

    public function testCannotApproveRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        Exchanger::saleRequest(User::find(1), 'stb', 100, 1.5);

        $this->expectException(NotEnoughFundsException::class);

        $userTo = User::find(2);
        Exchanger::approve(ExchangeRequest::find(1), $userTo);
    }

    public function testCannotApproveOwnRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        Exchanger::saleRequest(User::find(1), 'stb', 10, 1.5);

        $this->expectException(IsOwnExchangeRequestException::class);

        $userTo = User::find(1);
        Exchanger::approve(ExchangeRequest::find(1), $userTo);
    }

    public function testDeleteCreatedRequests()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $userFrom = User::find(1);

        $request = Exchanger::purchaseRequest($userFrom, 'stb', 10, 1.5);
        Exchanger::deleteRequest($request);

        $this->assertEquals($request->userFrom->gnr, 100);

        $request = Exchanger::saleRequest($userFrom, 'stb', 10, 1.5);
        Exchanger::deleteRequest($request);

        $this->assertEquals($request->userFrom->stb, 100);
    }

    public function testCannotDeleteApprovedRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $userFrom = User::find(1);
        $request = Exchanger::purchaseRequest($userFrom, 'stb', 10, 1.5);

        $userTo = User::find(2);
        Exchanger::approve($request, $userTo);

        $this->expectException(CannotDeleteApprovedRequestExceprion::class);

        Exchanger::deleteRequest($request);
    }

    public function testGetRequests()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $userFrom = User::find(1);

        $request = Exchanger::purchaseRequest($userFrom, 'stb', 10, 1.2);
        Exchanger::purchaseRequest($userFrom, 'gnr', 10, .5);
        Exchanger::saleRequest($userFrom, 'stb', 10, .7);

        $userTo = User::find(2);

        Exchanger::approve($request, $userTo);

        Exchanger::purchaseRequest($userTo, 'stb', 10, 1.2);
        Exchanger::purchaseRequest($userTo, 'gnr', 10, .5);
        Exchanger::saleRequest($userTo, 'stb', 10, .7);

        $this->assertEquals(Exchanger::allRequests($userFrom)->count(), 3);
        $this->assertEquals(Exchanger::approvedRequests($userFrom)->count(), 1);
        $this->assertEquals(Exchanger::pendingRequests($userFrom)->count(), 2);
    }

}
