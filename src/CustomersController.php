<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class CustomersController
{

    private $pdo;
    private $otp_timeout;

    public function __construct(
        PDO $pdo,
        ContainerInterface $c,
    ) {
        $this->pdo = $pdo;
        $this->pump_id = $c->get('settings')['pump_id'];
        // $this->userOps = $userOps;
        // $this->otpOps = $otpVerify;
        // $this->tokenOps = $tokenOps;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        $ret_array = array();

        $stmt = $this->pdo->prepare('SELECT * FROM customers WHERE cust_pump_id = :pump_id ORDER BY cust_company');
        $stmt->execute([
            'pump_id'     => $this->pump_id
        ]);
        $results = $stmt->fetchAll();
	

		if($stmt->rowCount() > 0)
		{			
			foreach ($results as $row) {
				$json=array();
				$json["cust_id"]	 	 		= $row["cust_id"];
				$json["cust_f_name"]	 		= $row["cust_f_name"];
				$json["cust_m_name"]	 		= $row["cust_m_name"];
				$json["cust_l_name"]	 		= $row["cust_l_name"];
				$json["cust_ph_no"]	 	 		= $row["cust_ph_no"];
				$json["cust_pump_id"]	 		= $row["cust_pump_id"];
				$json["cust_car_num"]	 		= $row["cust_car_num"];
				$json["cust_post_paid"]	 		= $row["cust_post_paid"];
				$json["cust_balance"]	 		= $row["cust_balance"];
				$json["cust_outstanding"]	 	= $row["cust_outstanding"];
				$json["cust_company"]	 		= $row["cust_company"];
				$json["cust_gst"]	 			= $row["cust_gst"];
				$json["cust_credit_limit"]	 	= $row["cust_credit_limit"];
				$json["cust_last_updated"]	 	= $row["cust_last_updated"];
				array_push($ret_array, $json);
			}
			
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
}
