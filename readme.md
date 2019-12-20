#### Create purchase request

```php
ExchangeRequest::create(User::find(1), 'purchase', 'stb', 10, .5);
```

#### Create sale request

```php
ExchangeRequest::create(User::find(1), 'sale', 'stb', 10, .5);
```

#### Approve request

```php
ExchangeRequest::find(1)->approve(User::find(2));
```

#### Delete request

```php
ExchangeRequest::find(1)->delete();
```

#### Getting requests

```php
$userFrom = User::find(1);

$userFrom->exchangeRequests(); //All user requests
$userFrom->pendingExchangeRequests(); //Pending user requests
$userFrom->approvedExchangeRequests(); //Approved user requests
```
