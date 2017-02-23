<?php

namespace ahmadrezaei\yii\mellatbank\components;

use ahmadrezaei\yii\mellatbank\models\MellatbankLog;
use yii\base\Component;
use yii\base\ErrorException;
use yii\web\HttpException;


/**
 * Class Mellatbank
 * @package ahmadrezaei\yii\mellatbank
 */
class Mellatbank extends Component
{
    /**
     * @var string
     */
    public $terminal = '';

    /**
     * @var string
     */
    public $username = '';

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var string
     */
    public $transactionId = '';

    /**
     * @var int
     */
    public $resultCode = null;

    /**
     * @var bool
     */
    public $renderAjax = false;

    /**
     * @var bool
     */
    public $mysql = false;

    /**
     * @var int
     */
    public $startOrderID = 100000;


    /**
     * Redirect User to Payment Page
     * @param $amount
     * @param $callBackUrl
     * @throws HttpException
     */
    public function createPayment($amount, $callBackUrl)
    {
        if(class_exists('nusoap_client') == false) {
            throw new HttpException(500, '"nusoap_client" class not found!');
        }

        $orderId = rand(10000,99999);

        // check if use mysql
        if ($this->mysql) {
            $model = new MellatbankLog();
            if($model->isEmpty()) {
                $model->id = $this->startOrderID;
            }
            if ($model->save()) {
                $orderId = $model->id;
            }
        }

        $client = new \nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' ) ;
        $terminalId = $this->terminal ;
        $userName = $this->username;
        $userPassword = $this->password;
        $localDate = date('ymj');
        $localTime = date('His');
        $additionalData = '';
        $payerId = 0;
        $err = $client->getError();
        if ($err) {
            echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
            die();
        }
        $parameters = [
            'terminalId' => $terminalId,
            'userName' => $userName,
            'userPassword' => $userPassword,
            'orderId' => $orderId,
            'amount' => $amount,
            'localDate' => $localDate,
            'localTime' => $localTime,
            'additionalData' => $additionalData,
            'callBackUrl' => $callBackUrl,
            'payerId' => $payerId
        ];
        $result = $client->call('bpPayRequest', $parameters, 'http://interfaces.core.sw.bps.com/');
        if ($client->fault) {
            echo '<h2>Fault</h2><pre>';
            print_r($result);
            echo '</pre>';
            die();
        } else {
            $resultStr  = $result;
            $err = $client->getError();
            if ($err) {
                echo '<h2>Error</h2><pre>' . $err . '</pre>';
                die();
            } else {
                $res = explode (',',$resultStr);
                $this->resultCode = $res[0];
                if ($this->resultCode == "0") {
                    $RefId =$res[1];
                    $controller =  \Yii::$app->controller;
                    if( $this->renderAjax ) {
                        echo $controller->renderAjax('@vendor/ahmadrezaei/yii2-mellatbank/views/form', [
                            'RefId' => $RefId
                        ]);
                    } else {
                        echo $controller->render('@vendor/ahmadrezaei/yii2-mellatbank/views/form', [
                            'RefId' => $RefId
                        ]);
                    }
                } else {
                    $this->error($this->resultCode);
                }
            }
        }
    }
    /**
     * @param null $params
     * @return array|bool
     * @throws ErrorException
     */
    public function verify($params = null)
    {
        if ($params == null) {
            $params = \Yii::$app->getRequest()->post();
        }
        if(empty($params) || is_array($params) == false)
        {
            throw new ErrorException(500, 'POST body is NULL!');
        }

        // check if use mysql
        if ($this->mysql) {
            $orderId = $params["SaleOrderId"];
            $model = MellatbankLog::findOne($orderId);
            if ($model !== null) {
                if(!empty($params["SaleReferenceId"])) {
                    $model->saleReferenceId = $params["SaleReferenceId"];
                }
                if(!empty($params["CardHolderPan"])) {
                    $model->CardHolderPan = $params["CardHolderPan"];
                }
                $model->data = print_r($params, true);
                $model->save();
            }
        }

        // set result code
        $this->resultCode = $params["ResCode"];
        if( $this->resultCode == 0 )
        {
            if( $this->verifyPayment($params) == true ) {
                if( $this->settlePayment($params) == true )
                {
                    // Save SaleReferenceId (trans id)
                    $this->transactionId = $params["SaleReferenceId"];

                    // save data to database
                    if ($this->mysql &&  isset($model)) {
                        $model->status = MellatbankLog::STATUS_SUCCESS;
                        $model->save();
                    }

                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Verify payment by calling mellat bank API
     * @param $params
     * @return bool
     */
    protected function verifyPayment($params)
    {
        $client = new \nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' ) ;
        $orderId = $params["SaleOrderId"];
        $verifySaleOrderId = $params["SaleOrderId"];
        $verifySaleReferenceId = $params['SaleReferenceId'];
        $err = $client->getError();
        if ($err) {
            echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
            die();
        }
        $parameters = [
            'terminalId'=> $this->terminal,
            'userName'=> $this->username,
            'userPassword'=> $this->password,
            'orderId' => $orderId,
            'saleOrderId' => $verifySaleOrderId,
            'saleReferenceId' => $verifySaleReferenceId
        ];
        $result = $client->call('bpVerifyRequest', $parameters, 'http://interfaces.core.sw.bps.com/');
        if ($client->fault) {
            echo '<h2>Fault</h2><pre>';
            print_r($result);
            echo '</pre>';
            die();
        } else {
            $resultStr = $result;
            $err = $client->getError();
            if ($err) {
                echo '<h2>Error</h2><pre>' . $err . '</pre>';
                die();
            } else {
                if( $resultStr == '0' ) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Settle payment by calling mellat bank API
     * @param $params
     * @return bool|mixed
     */
    protected function settlePayment($params)
    {
        $client = new \nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' ) ;
        $orderId = $params["SaleOrderId"];
        $settleSaleOrderId = $params["SaleOrderId"];
        $settleSaleReferenceId = $params['SaleReferenceId'];
        $err = $client->getError();
        if ($err) {
            echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
            die();
        }
        $parameters = [
            'terminalId'=> $this->terminal,
            'userName'=> $this->username,
            'userPassword'=> $this->password,
            'orderId' => $orderId,
            'saleOrderId' => $settleSaleOrderId,
            'saleReferenceId' => $settleSaleReferenceId
        ];
        $result = $client->call('bpSettleRequest', $parameters, 'http://interfaces.core.sw.bps.com/');
        if ($client->fault) {
            echo '<h2>Fault</h2><pre>';
            print_r($result);
            echo '</pre>';
            die();
        } else {
            $resultStr = $result;
            $err = $client->getError();
            if ($err) {
                echo '<h2>Error</h2><pre>' . $err . '</pre>';
                die();
            } else {
                if( $resultStr == '0' ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Return Error in string format
     * @param int $number
     * @throws HttpException
     */
    protected function error( $number )
    {
        $err = 'خطای درگاه پرداخت بانک ملت!';
        switch($number) {
            case 31 :
                $err = "پاسخ نامعتبر است!";
                break;
            case 17 :
                $err = "کاربر از انجام تراکنش منصرف شده است!";
                break;
            case 21 :
                $err = "پذیرنده نامعتبر است!";
                break;
            case 25 :
                $err = "مبلغ نامعتبر است!";
                break;
            case 34 :
                $err = "خطای سیستمی!";
                break;
            case 41 :
                $err = "شماره درخواست تکراری است!";
                break;
            case 421 :
                $err = "ای پی نامعتبر است!";
                break;
            case 412 :
                $err = "شناسه قبض نادرست است!";
                break;
            case 45 :
                $err = "تراکنش از قبل ستل شده است";
                break;
            case 46 :
                $err = "تراکنش ستل شده است";
                break;
            case 35 :
                $err = "تاریخ نامعتبر است";
                break;
            case 32 :
                $err = "فرمت اطلاعات وارد شده صحیح نمیباشد";
                break;
            case 43 :
                $err = "درخواست verify قبلا صادر شده است";
                break;
        }
        throw new HttpException(500, $err);
    }
}
