<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class ReceiptsController
{

    private $pdo;
    private $otp_timeout;

    
    

    public function __construct(
        PDO $pdo,
        ContainerInterface $c,
    ) {
        $this->pdo = $pdo;
        $this->pump_id = $c->get('settings')['pump_id'];                
        $this->RECEIPT_USED = -2;
        $this->RECEIPT_INVALID = -1;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        
        $ret_array = array();

        $rnum = trim($args['r_num']);
        if($rnum == ""){
            return $this->errorReturn($request, $response, "Enter Valid Receipt Num");
        }
        
        // 1. check if receipt is already used
        // 2. if not used get cust id from range
        // 3. invalid receipt

        //$cust_id = $this->getCustId($rnum);
        
        // if($cust_id == $this->RECEIPT_USED) {
        //     $ret_array['success'] = false;
        //     $ret_array['msg'] = 'Receipt already used';
        // }
        // else if ($cust_id == $this->RECEIPT_INVALID) {
        //     $ret_array['success'] = false;
        //     $ret_array['msg'] = 'Invalid Receipt Number';
        // }
        // else {
        //     $ret_array = $this->getCustDetails($cust_id);
        // }

        if($this->isReceiptAlreadyUsed($rnum)){
            $ret_array['success'] = false;
            $ret_array['msg'] = 'Receipt already used';  
        }
        else{
            $ret_array['success'] = true;            
        }

                
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


    private function isReceiptAlreadyUsed($rbook_num) {
            
        $stmt = $this->pdo->prepare('SELECT 1 FROM transactions WHERE receipt_no = :rbook_num');
        $stmt->execute(['rbook_num' => $rbook_num]);        
        $row = $stmt->fetch();
        if ($row) {
            return true;
        }
        else {
            return false;
        }


        // $stmt = $this->pdo->prepare('SELECT 1 FROM transactions WHERE receipt_no = :rbook_num');
        // $stmt->execute(['rbook_num' => $rbook_num]);        
        // $row = $stmt->fetch();
        // if ($row) {
        //     return $this->RECEIPT_USED;
        // }
        // else {
        //     $stmt2 = $this->pdo->prepare('SELECT cust_id FROM receipt_books WHERE :rbook_num BETWEEN min and max');
        //     $stmt2->execute(['rbook_num' => $rbook_num]);
        //     $row2 = $stmt2->fetch();
        //     if ($row2) {
        //         return $row2["cust_id"];	
        //     }
        //     else {
        //         return $this->RECEIPT_INVALID;
        //     }
        // }
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
