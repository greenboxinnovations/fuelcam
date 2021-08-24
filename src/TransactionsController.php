<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class TransactionsController
{

    private $pdo;
    private $otp_timeout;

    public function __construct(
        PDO $pdo,
        ContainerInterface $c,
    ) {
        $this->pdo = $pdo;
        $this->pump_id = $c->get('settings')['pump_id'];
        $this->num_set_rates_per_day =  $c->get('settings')['set_rates_per_day'];
        $this->android_double_entry_time_seconds =  $c->get('settings')['android_double_entry_time_seconds'];
        // $this->userOps = $userOps;
        // $this->otpOps = $otpVerify;
        // $this->tokenOps = $tokenOps;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        // receive number
        $postData = (array)$request->getParsedBody();

        // check post vars
        if (    !array_key_exists("car_fuel", $postData) ||
                !array_key_exists("fuel_rate", $postData) ||
                !array_key_exists("car_id", $postData) ||
                !array_key_exists("amount", $postData) ||
                !array_key_exists("liters", $postData) ||
                !array_key_exists("cust_id", $postData) ||
                !array_key_exists("user_id", $postData) ||
                !array_key_exists("receipt_no", $postData) ||
                !array_key_exists("nozzle_qr", $postData) ||
                !array_key_exists("shift", $postData) ||
                !array_key_exists("pump_id", $postData)
        ) {
            return $this->errorReturn($request, $response, "Access Denied");
        }
                           

        // ret
        $ret_array = array();
        $ret_array['success'] = false;

        $date = date('Y-m-d');

        // accept vars
        $cust_id    = $request->getParsedBody()['cust_id'];
        $car_id     = $request->getParsedBody()['car_id'];
        $car_fuel   = $request->getParsedBody()['car_fuel'];
        
        $fuel_rate  = $request->getParsedBody()['fuel_rate'];        
        $amount     = $request->getParsedBody()['amount'];
        $liters     = $request->getParsedBody()['liters'];
        
        $user_id    = $request->getParsedBody()['user_id'];
        $pre_shift  = $request->getParsedBody()['shift'];       // change shift letters to numeric mapping
        $shift 		= ($pre_shift == "a") ? 1 : 2;

        $receipt_no = $request->getParsedBody()['receipt_no'];

        $nozzle_qr  = $request->getParsedBody()['nozzle_qr'];        
        $pump_id    = $request->getParsedBody()['pump_id'];


		// 1. DUPLICATE RECEIPT_NO TEMPORARY FIX
		// 2. get trans_string from nozzle_qr
		// 3. prevent same transaction using time 20seconds
        // 4. prevent same transaction same day same car
        // 5. insert transaction
        // 6. print n receipts
        // 7. update sync table


        // 1. DUPLICATE RECEIPT_NO TEMPORARY FIX
        //$receipt_no = $this->setReceiptZeroIfDuplicate($receipt_no);

        // 2. get trans_string from nozzle_qr
        $trans_string = $this->getTransStringFromNozzleQR($nozzle_qr);
        if($trans_string == -1){
            return $this->errorReturn($request, $response, "Nozzle QR trans_string error");
        }

        // get car plate no if id != -99
        //$car_plate_no = $this->get

        
        
        // 3. prevent same transaction using time 20seconds        
        // 4. prevent same transaction same day same car
        if(!$this->preventDoubleTrans($car_id, $amount)) {
            return $this->errorReturn($request, $response, "Duplicate Transaction");
        }        

        // 5. insert transaction        
        $this->insertTransaction($pump_id, $cust_id, $car_id, $user_id, $car_fuel, $amount, $fuel_rate, $liters, $shift, $trans_string, $receipt_no);

        // 6. print n receipts
        //$this->printReceipts();

        // 7. update sync table
        

        // HTTP response
        $ret_array['success'] = true;        
        $response->getBody()->write((string)json_encode($ret_array));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    private function errorReturn(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $message
    ) {
        $err_data = array("message" => $message);

        // HTTP response
        $response->getBody()->write(json_encode($err_data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(409);
    }


        
    private function setReceiptZeroIfDuplicate($receipt_no){}

    private function getTransStringFromNozzleQR($nozzle_qr){
        $stmt = $this->pdo->prepare('SELECT trans_string FROM cameras WHERE cam_qr_code = :nozzle_qr');
        $stmt->execute(['nozzle_qr'     => $nozzle_qr]);        
		$row = $stmt->fetch();
        if (!$row) {
            return -1;
        }
        return $row['trans_string'];        
    }

    // 1. check if last updated is more than 20 seconds
    // 2. check if amount for car_id and date already exists
    //    return -1 for any mismatch
    private function preventDoubleTrans($car_id, $amount){

        $date       = date('Y-m-d');
        $timestamp 	= date("Y-m-d H:i:s");

        $stmt = $this->pdo->prepare('SELECT last_updated, amount FROM transactions WHERE car_id = :car_id AND  date(date) = :mydate');
        $stmt->execute([
            'car_id' => $car_id,
            'mydate' => $date
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            // no data found for cur car on cur date
            return true;
        }

        $last_found = $row['last_updated'];
		$diff = strtotime($timestamp) - strtotime($last_found);        

        // prevent double entry
        if($diff < $this->android_double_entry_time_seconds){
            return false;
        }

        if($amount == $row['amount']){
            return false;
        }

        // amount is ok and insert time diff is more than 20 seconds
        return true;
    }

    private function preventSameTransSameCarSameDay($pump_id, $cust_id, $car_id){}

    private function insertTransaction($pump_id, $cust_id, $car_id, $user_id, $car_fuel, $amount, $fuel_rate, $liters, $shift, $trans_string, $receipt_no){

        $date 	= date("Y-m-d H:i:s");				

        $sql = "INSERT INTO `transactions` (`pump_id`,`cust_id`,`car_id`,`user_id`,`fuel`,`amount`,`rate`,`liters`,`date`,`last_updated`,`shift`,`trans_string`,`receipt_no`) 
                VALUES (:field1,:field2,:field3,:field4,:field5,:field6,:field7,:field8,:field9,:field10,:field11,:field12,:field13);";

        $stmt = $this->pdo->prepare('INSERT INTO transactions (pump_id, cust_id, car_id, user_id, fuel, amount, rate, liters, date, last_updated, shift, trans_string, receipt_no) 
                                    VALUES (:pump_id, :cust_id, :car_id, :user_id, :fuel, :amount, :rate, :liters, :date, :last_updated, :shift, :trans_string, :receipt_no)');
        $stmt->execute([
            'pump_id'	    => $pump_id,
            'cust_id'	    => $cust_id,
            'car_id'	    => $car_id,
            'user_id'	    => $user_id,
            'fuel'	        => $car_fuel,
            'amount'	    => $amount,
            'rate'	        => $fuel_rate,
            'liters'	    => $liters,
            'date'	        => $date,
            'last_updated'	=> $date,
            'shift'	        => $shift,
            'trans_string'  => $trans_string,
            'receipt_no'	=> $receipt_no
        ]);

        // get last trans-id and append S to it
        // S for scan and M for manual entry
        $trans_id = $this->getLastTransId();
        $transaction_no = "P".$pump_id."S".$trans_id;
        $this->updateTransNo($transaction_no, $trans_id);
    }

    private function printReceipts(){}

    private function isQRValid($qr_code){
        $stmt = $this->pdo->prepare('SELECT * FROM codes WHERE qr_code = :qr_code');
        $stmt->execute(['qr_code'     => $qr_code]);
        $row = $stmt->fetch();
        if (!$row) {return false;}
        return true;
    }


    private function getLastTransId(){
        $stmt = $this->pdo->prepare('SELECT trans_id FROM transactions WHERE 1 ORDER BY trans_id DESC LIMIT 1');        
        $stmt->execute();
		$row = $stmt->fetch();
        if (!$row) {
            return -1;
        }
        return $row['trans_id'];
    }

    private function updateTransNo($transaction_no, $trans_id) {
        
        $stmt = $this->pdo->prepare('UPDATE transactions SET transaction_no = :transaction_no WHERE trans_id = :trans_id');
        $stmt->execute([
            'transaction_no' => $transaction_no,
            'trans_id' => $trans_id
        ]);
    }
    
}
