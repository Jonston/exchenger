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
