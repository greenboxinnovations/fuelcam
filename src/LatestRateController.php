<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class LatestRateController
{

    private $pdo;
    private $otp_timeout;

    
    

    public function __construct(
        PDO $pdo,
        ContainerInterface $c,
    ) {
        $this->pdo = $pdo;        
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        
        $ret_array = array();

        
        $stmt = $this->pdo->prepare('SELECT * FROM rates ORDER BY rate_id DESC LIMIT 1');        
        $stmt->execute();
        $row = $stmt->fetch();
        if($row){
            $ret_array['success'] = true;
            $ret_array['rate_set'] = true;
            $ret_array['petrol_rate'] = $row['petrol'];
            $ret_array['diesel_rate'] = $row['diesel'];
        }
        else {
            $ret_array['success'] = false;
            $ret_array['rate_set'] = false;
        }

                
        $response->getBody()->write((string)json_encode($ret_array));
        // $response->getBody()->write((string)json_encode('123'));
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


    
    
    private function getCustDetails($cust_id) {

        $ret_array = array();

        $stmt = $this->pdo->prepare('SELECT * FROM customers WHERE cust_id = :cust_id');
        $stmt->execute([
            'cust_id' => $cust_id,
        ]);
        $row = $stmt->fetch();
        if($row){
            $p = $row['petrol'];
            $d = $row['diesel'];

            $ret_array['success'] 	= true;
            $ret_array['cust_id'] 	= $cust_id;
            $ret_array['cust_name'] = $row['cust_disp_name'];

        }
        else {
            $ret_array['success'] = false;
            $ret_array['msg'] = "Customer error";
        }

        return $ret_array;
    }
}
