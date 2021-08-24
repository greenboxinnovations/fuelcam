<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class RatesController
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
        if ( !array_key_exists("petrol", $postData) ||
             !array_key_exists("diesel", $postData) || 
             !array_key_exists("pump_id", $postData) ||
             !array_key_exists("user_id", $postData)
             ) {
            return $this->errorReturn($request, $response, "Access Denied");
        }
        
        // assign vars
        $petrol_rate  = $request->getParsedBody()['petrol'];
        $diesel_rate  = $request->getParsedBody()['diesel'];
        $pump_id = $request->getParsedBody()['pump_id'];
        $user_id = $request->getParsedBody()['user_id'];

        // ret
        $ret_array = array();
        $ret_array['success'] = false;

        $date = date('Y-m-d');


        

        // make sure user id is correct
        // get user name 
        // old code used 2 network requests
        // new code combines them
        $user_name = $this->getUserName($user_id);
        if($user_name == -1){
            return $this->errorReturn($request, $response, "Access Denied");
        }


        // select from db where phno
        $stmt = $this->pdo->prepare('SELECT * FROM rates WHERE date = :date');
        $stmt->execute([
            'date'     => $date,
        ]);
        $rows = $stmt->fetchAll();

        // < 2 in this case
        // so 2 times allowed
        // may change from pump to pump
        if($stmt->rowCount() < $this->num_set_rates_per_day){

            if(!$this->isDuplicateRates($date, $petrol_rate, $diesel_rate)){
                $this->insertRates($pump_id, $date, $petrol_rate, $diesel_rate);
            }               

            // updateSyncTable here
            $ret_array['rate_set'] = true;
            $ret_array['petrol_rate'] = $petrol_rate;
            $ret_array['diesel_rate'] = $diesel_rate;
            

            $ret_array['success'] = true;
            $ret_array['user_id'] = $user_id;
            $ret_array['pump_id'] = $this->pump_id;
            $ret_array['user_name'] = $user_name;
            $ret_array['date'] = date('Y-m-d');
            // $ret_array['date'] = "2021-01-01";

        }
        else{
            $ret_array['success'] = false;
            $ret_array['msg'] = "Rates already Set!";
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


    private function getUserName($user_id){
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE user_id = :user_id');
        $stmt->execute([
            'user_id'     => $user_id,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return -1;
        }
        else {
            return $row['name'];
        }
    }

    private function isDuplicateRates($date, $petrol_rate, $diesel_rate){
        // prevent a the same rates from being added again
        $stmt = $this->pdo->prepare('SELECT * FROM rates WHERE date = :date ORDER BY rate_id DESC LIMIT 1');
        $stmt->execute([
            'date'     => $date,
        ]);
        $row = $stmt->fetch();
        if($row){
            $p = $row['petrol'];
            $d = $row['diesel'];

            if(($petrol_rate == $p) && ($diesel_rate == $d)){
                return true;
            }
        }
        return false;
    }

    private function insertRates($pump_id, $date, $petrol_rate, $diesel_rate){
        // insert into rates
        $stmt = $this->pdo->prepare('INSERT INTO rates (pump_id, petrol, diesel, date) 
                VALUES (:pump_id, :petrol, :diesel, :date)');

        $stmt->execute([
        'pump_id' => $pump_id,
        'petrol'  => $petrol_rate,
        'diesel'  => $diesel_rate,
        'date'    => $date
        ]);


        // update sync table
        $last_updated = strtotime(date('Y-m-d H:i:s'));

        $stmt = $this->pdo->prepare('UPDATE sync SET id=id+1,last_updated=:last_updated WHERE table_name = "rates"');
        $stmt->execute([
            'last_updated' => $last_updated            
        ]);
    }
}
