yii2 iranian bank mellat gateway extension
==========================================
By this extension you can add mellat bank gateway to your yii2 project

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist ahmadrezaei/yii2-mellatbank "*"
```

or add

```
"ahmadrezaei/yii2-mellatbank": "*"
```

to the require section of your `composer.json` file.


Configuring application
-----

After extension is installed you need to setup auth client collection application component:

```php
return [
    'components' => [
        'mellatbank' => [
            'class' => 'ahmadrezaei\yii\mellatbank\Mellatbank',
            'username' => 'YOUR-USERNAME',
            'password' => 'YOUR-PASSWORD',
            'terminal' => 'YOUR-TERMINAL-ID',
        ]
        // ...
    ],
    // ...
];
```

If you want to save records in database, create migrations:

```php
php yii migrate -p=@vendor/ahmadrezaei/yii2-mellatbank/migrations 
```



Usage
-----

For create a payment request:

```php
$amount = 10000; // Rial
$callBackUrl = Url::to(['callback']); // callback url
/* @var $mellatbank \ahmadrezaei\yii\mellatbank\Mellatbank */
$mellatbank = Yii::$app->mellatbank;
$mellatbank->createPayment($amount, $callBackUrl);
```

For verify payment request:

```php
/* @var $mellatbank \ahmadrezaei\yii\mellatbank\Mellatbank */
$mellatbank = Yii::$app->mellatbank;
$result = $mellatbank->verify();
if( $result ) {
    // payment is successfull
    $transactionID = $mellatbank->transactionId;
    $resCode = $mellatbank->resultCode;
} else {
    // payment is unsuccessfull
    $resCode = $mellatbank->resultCode;
}
```