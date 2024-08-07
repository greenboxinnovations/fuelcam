<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class LoginController
{

    private $pdo;
    private $otp_timeout;

    public function __construct(
        PDO $pdo,
        ContainerInterface $c,
    ) {
        $this->pdo = $pdo;
        //$this->pump_id = $c->get('settings')['pump_id'];
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
        if (!array_key_exists("name", $postData) || !array_key_exists("pass", $postData)) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // check post vars
        if ((!array_key_exists("version", $postData)) || ($request->getParsedBody()['version'] != '1.5')) {
            return $this->errorReturn($request, $response, "App Version Mismatch");
        }


        // assign vars
        $myuser  = $request->getParsedBody()['name'];
        $pass    = $request->getParsedBody()['pass']; 
        $version    = $request->getParsedBody()['version'];       

        // ret
        $ret_array = array();
        $ret_array['success'] = false;


        // select from db where phno
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE name = :myuser');
        $stmt->execute([
            'myuser'     => $myuser,
        ]);
        $row = $stmt->fetch();
        $pump_id = $row['user_pump_id'];

        if(password_verify($pass, $row['pass'])){

            

            // check if user is operator
            // if($row['role'] != "operator"){
            //     return $this->errorReturn($request, $response, "Access Denied");
            // }

            // check if rates are set
            $stmt2 = $this->pdo->prepare('SELECT * FROM rates WHERE pump_id = :pump_id and date = :date ORDER BY rate_id DESC LIMIT 1');
            $stmt2->execute([
                'pump_id'  => $pump_id,
                'date'     => date('Y-m-d')                
            ]);
            $row2 = $stmt2->fetch();

            // no data found for date
            if (!$row2) {
                $ret_array['success'] = true;
                $ret_array['rate_set'] = false;
                $ret_array['user_id'] = (int) $row['user_id'];
                $ret_array['pump_id'] = (int) $pump_id;
                 $ret_array['role'] = $row['role'];


            }
            else{
                $ret_array['success'] = true;
                $ret_array['rate_set'] = true;
                $ret_array['petrol_rate'] = $row2['petrol'];
                $ret_array['diesel_rate'] = $row2['diesel'];


                $ret_array['role'] = $row['role'];               


                $ret_array['user_id'] = (int) $row['user_id'];
                $ret_array['pump_id'] = (int) $pump_id;
                $ret_array['user_name'] = $myuser;
                $ret_array['date'] = date('Y-m-d');
                // $ret_array['date'] = "2021-01-01";
            }

            
            
        }else{
            $ret_array['msg'] = "Password Error";
        }

        // sleep(12);


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
}
