<?php

use App\Exceptions\CannotDeleteApprovedRequestExceprion;
use App\Exceptions\NotEnoughFundsException;
use App\Libraries\ExchangeRequest;
use App\User;
use Illuminate\Support\Facades\DB;

class ExchangeRequestTest extends TestCase
{
    public function testCreatePurchaseRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $request = ExchangeRequest::create(User::find(1), 'purchase', 'stb', 10, .5);
        $userFrom = $request->userFrom;

        $this->assertEquals([
            'operation' => 'purchase',
            'currency'  => 'stb',
            'amount'    => 10,
            'rate'      => .5
        ], collect($request->toArray())->only('currency', 'amount', 'operation', 'rate')->all());
        $this->assertEquals($userFrom->gnr, 95);
    }

    public function testApprovePurchaseRequest()
    {
        $userTo = User::find(2);
        $request = ExchangeRequest::find(1)->approve($userTo);

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

        $request = ExchangeRequest::create(User::find(1), 'sale', 'stb', 10, .5);
        $userFrom = $request->userFrom;

        $this->assertEquals([
            'operation' => 'sale',
            'currency'  => 'stb',
            'amount'    => 10,
            'rate'      => .5
        ], collect($request->toArray())->only('currency', 'amount', 'operation', 'rate')->all());
        $this->assertEquals($userFrom->stb, 90);
    }

    public function testApproveSaleRequest()
    {
        $userTo = User::find(2);
        $request = ExchangeRequest::find(1)->approve($userTo);

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

    public function testCannotCreatePurchaseRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 1)->create();

        $this->expectException(NotEnoughFundsException::class);

        ExchangeRequest::create(User::find(1), 'purchase', 'stb', 100, 1.5);
    }

    public function testCannotCreateSaleRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 1)->create();

        $this->expectException(NotEnoughFundsException::class);

        ExchangeRequest::create(User::find(1), 'sale', 'stb', 110, 1.5);
    }

    public function testCannotApproveRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $request = ExchangeRequest::create(User::find(1), 'sale', 'stb', 100, 1.5);

        $this->expectException(NotEnoughFundsException::class);

        $request->approve(User::find(2));
    }

    public function testDeleteCreatedRequests()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $userFrom = User::find(1);

        $request = ExchangeRequest::create($userFrom, 'purchase', 'stb', 10, 1.5);
        $request->delete();

        $this->assertEquals($request->userFrom->gnr, 100);

        $request = ExchangeRequest::create($userFrom, 'sale', 'stb', 10, 1.5);
        $request->delete();

        $this->assertEquals($request->userFrom->stb, 100);
    }

    public function testCannotDeleteApprovedRequest()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $userFrom = User::find(1);
        $request = ExchangeRequest::create($userFrom, 'purchase', 'stb', 10, .5);

        $userTo = User::find(2);
        $request->approve($userTo);

        $this->expectException(CannotDeleteApprovedRequestExceprion::class);

        $request->delete();
    }

    public function testGetRequests()
    {
        DB::table('exchange_requests')->truncate();
        DB::table('users')->truncate();

        factory(User::class, 2)->create();

        $userFrom = User::find(1);

        $request = ExchangeRequest::create($userFrom, 'purchase', 'stb', 10, 1.2);
        ExchangeRequest::create($userFrom, 'purchase', 'gnr', 10, .5);
        ExchangeRequest::create($userFrom, 'sale', 'stb', 10, .7);

        $userTo = User::find(2);
        $request->approve($userTo);

        ExchangeRequest::create($userTo, 'purchase', 'stb', 10, 1.2);
        ExchangeRequest::create($userTo, 'purchase', 'gnr', 10, .5);
        ExchangeRequest::create($userTo, 'sale', 'stb', 10, .7);

        $this->assertEquals($userFrom->exchangeRequests()->count(), 3);
        $this->assertEquals($userFrom->pendingExchangeRequests()->count(), 2);
        $this->assertEquals($userFrom->approvedExchangeRequests()->count(), 1);
    }


}
