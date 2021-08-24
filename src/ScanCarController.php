<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class ScanCarController
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
        if ( !array_key_exists("qr_code", $postData)) {
            return $this->errorReturn($request, $response, "Access Denied");
        }
        
        // assign vars
        $qr_code  = $request->getParsedBody()['qr_code'];        

        // ret
        $ret_array = array();
        $ret_array['success'] = false;

        $date = date('Y-m-d');


        // make sure user id is correct
        // get user name 
        // old code used 2 network requests
        // new code combines them
        $row = $this->getCarIdFromQR($qr_code);
        if($row == -2){
            $ret_array['msg'] = "QR code is unassigned";
        }
        else if($row == -1){
            $ret_array['msg'] = "QR code is invalid";
        }
        else if($row == -3){
            $ret_array['msg'] = "Car is inactive";
        }else{
            $ret_array['success']   = true;
            $ret_array['cust_id']   = (int)$row['cust_id'];
            $ret_array['cust_name'] = $row['cust_disp_name'];
            $ret_array['car_no']    = $row['car_no_plate'];
            $ret_array['car_id']    = (int)$row['car_id'];
            $ret_array['car_fuel']  = $row['car_fuel_type'];
        }

        // HTTP response
        // $otp_data = array("otp" => "working");
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


    private function getCarIdFromQR($qr_code){
        $stmt = $this->pdo->prepare('SELECT a.cust_id, a.cust_disp_name, b.* FROM customers a 
                                    LEFT JOIN  cars b
                                    ON a.cust_id = b.car_cust_id
                                    WHERE a.cust_id IN (SELECT car_cust_id FROM cars WHERE car_qr_code = :qr_code) 
                                    AND b.car_qr_code = :qr_code');

        $stmt->execute([
            'qr_code'     => $qr_code,
        ]);
        $row = $stmt->fetch();

        // no row
        if (!$row) {

            // check if code exists
            if($this->isQRValid($qr_code)){
                return -2; // qr is unassigned
            }
            return -1; // qr is completely invalid
        }
        else {

            // car could be inactive
            // check here
            if($row['status'] == "inactive"){
                return -3;
            }

            return $row;
        }
    }

    private function isQRValid($qr_code){
        $stmt = $this->pdo->prepare('SELECT * FROM codes WHERE qr_code = :qr_code');

        $stmt->execute([
            'qr_code'     => $qr_code,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }        

        return true;
    }
}
