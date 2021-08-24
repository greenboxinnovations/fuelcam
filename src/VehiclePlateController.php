<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class VehiclePlateController
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

    	if (!array_key_exists("no_plate",$args)){
    		return $this->errorReturn($request, $response, "Enter Vehicle Num");
    	}

    	$no_plate = trim($args['no_plate']);
        if($no_plate == ""){
            return $this->errorReturn($request, $response, "Enter Vehicle Num");
        }

        
        $no_plate = strtolower(preg_replace("/[\W_]+/u", '', $no_plate));

    	
        

        $stmt = $this->pdo->prepare('SELECT a.*,b.cust_disp_name,b.cust_id FROM cars a
									JOIN customers b
									ON a.car_cust_id = b.cust_id
									WHERE a.car_no_plate = :no_plate 
									AND a.status="active"  LIMIT 1');
		$stmt->execute([
            'no_plate'     => $no_plate,
        ]);        
        $row = $stmt->fetch();
        if (!$row) {
            return $this->errorReturn($request, $response, "No Vehicle Found");
        }


        $ret_array = array();
        $ret_array['car_id'] = $row['car_id'];
        $ret_array['cust_id'] = $row['cust_id'];
        $ret_array['car_fuel'] = $row['car_fuel_type'];
        $ret_array['cust_disp_name'] = $row['cust_disp_name'];

		
        // HTTP response
        $otp_data = array("otp" => "working");
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
