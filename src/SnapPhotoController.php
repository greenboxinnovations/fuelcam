<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class SnapPhotoController
{

    private $pdo;
    private $otp_timeout;

    public function __construct(
        PDO $pdo,
        ContainerInterface $c,
    ) {
        $this->pdo = $pdo;
        $this->pump_id = $c->get('settings')['pump_id'];
        //$this->num_set_rates_per_day =  $c->get('settings')['set_rates_per_day'];
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
        if ( !array_key_exists("photo_type", $postData) ||             
             !array_key_exists("nozzle_qr", $postData)
        ) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // assign vars        
        $photo_type  = $request->getParsedBody()['photo_type'];    
        $nozzle_qr  = $request->getParsedBody()['nozzle_qr'];    

        // ret
        $ret_array = array();
        $ret_array['success'] = false;

        $date = date('Y-m-d');

        // is nozzle_qr valid
        // if start generate unique trans-string
        // if stop select trans-string using nozzle-qr        
        if (!$this->isNozzleQRValid($nozzle_qr)) {
            return $this->errorReturn($request, $response, "Invalid Nozzle QR");
        }

        // $trans_string = $this->generateRand();
        
        if($photo_type == 'start') {
            $trans_string = $this->generateRand();

            $stmt = $this->pdo->prepare('UPDATE cameras SET status = 1, trans_string = :trans_string, type = :photo_type WHERE cam_qr_code = :nozzle_qr');
            $stmt->execute([
                'trans_string' => $trans_string,
                'photo_type' => $photo_type,
                'nozzle_qr' => $nozzle_qr
            ]);

            $ret_array['success'] = true;
            $ret_array['photo_url'] =  "uploads/".$date."/".$trans_string."_start.jpeg";
            // $ret_array['photo_url'] = "https://homepages.cae.wisc.edu/~ece533/images/airplane.png";

        }
        else if($photo_type == 'stop') {
            
            $stmt = $this->pdo->prepare('SELECT trans_string FROM cameras WHERE cam_qr_code = :nozzle_qr');
            $stmt->execute([
                'nozzle_qr' => $nozzle_qr
            ]);
            $row = $stmt->fetch();
            if($row) {
                $ret_array['success'] = true;
                // $ret_array['photo_url'] = "https://homepages.cae.wisc.edu/~ece533/images/airplane.png";
                $ret_array['photo_url'] =  "uploads/".$date."/".$row['trans_string']."_stop.jpeg";
            }
            else {
                $ret_array['success'] = false;
                $ret_array['message'] = "nozzle table trans-string missing";
            }

            // update the camera / nozzle again for C++
            $stmt = $this->pdo->prepare('UPDATE cameras SET status = 1, type = :photo_type WHERE cam_qr_code = :nozzle_qr');
            $stmt->execute([
                'photo_type' => $photo_type,
                'nozzle_qr' => $nozzle_qr
            ]);

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


    private function generateRand() {
    
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $length = 10;
        $trans_string = '';
        for ($i = 0; $i < $length; $i++) {
            $trans_string .= $characters[rand(0, $charactersLength - 1)];
        }
    
        
        $stmt = $this->pdo->prepare('SELECT 1 FROM trans_string WHERE trans_string = :trans_string');
        $stmt->execute([
            'trans_string'     => $trans_string,
        ]);
        $row = $stmt->fetch();

        if (!$row) {

            $stmt = $this->pdo->prepare('INSERT INTO trans_string (trans_string) 
            VALUES (:trans_string)');

            $stmt->execute([
                'trans_string' => $trans_string
            ]);

            return $trans_string;
        }
        else {
            $this->generateRand();
        } 
           
    }

    private function isNozzleQRValid($nozzle_qr) {
        $stmt = $this->pdo->prepare('SELECT * FROM cameras WHERE cam_qr_code = :nozzle_qr');
        $stmt->execute([
            'nozzle_qr' => $nozzle_qr
        ]);
        $row = $stmt->fetch();
        if (!$row) {return false;}        
        return true;
    }
}
