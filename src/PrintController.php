<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

use PDO;

class item {
    private $vh_no;
    private $fuel;
    private $rate;
    private $ltr;
    private $amount;

    public function __construct($vh_no = '', $fuel = '',$rate = '',$ltr = '',$amount = '') {
        $this -> vh_no = $vh_no;
        $this -> fuel = $fuel;
        $this -> rate = $rate;
        $this -> ltr = $ltr;
        $this -> amount = $amount;

    }
    
    public function __toString() {
        $one = str_pad($this -> vh_no, 15);
        $two = str_pad($this -> fuel, 8);
        $three = str_pad($this -> rate, 7);
        $four = str_pad($this -> ltr,7);
        $five = str_pad($this -> amount, 0);
        
        return "$one$two$three$four$five\n";
    }
}


final class PrintController
{

    private $pdo;
    private $otp_timeout;
    private $printer_ip;

    
    public function __construct(
        PDO $pdo,
        ContainerInterface $c    
    ) {
        $this->pdo = $pdo;        
        $this->printer_ip = $c->get('settings')['printer_ip'];
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        
        $ret_array = array();
        /**/
        $trans_id = trim($args['trans_id']);
        if($trans_id == "1"){
            return $this->errorReturn($request, $response, "Enter trans_id");
            // return $this->errorReturn($request, $response, $_SERVER["DOCUMENT_ROOT"]);
        }


        if(!$this->isHostOnline($this->printer_ip)){
            return $this->errorReturn($request, $response, "Printer offline");
        }
        


        
        // $printer = new Printer($connector);
        // try {
        //     $printer -> text("Hello World!\n");            
        // } finally {
        //     $printer -> cut();
        //     $printer -> close();
        // }

        /* Start the printer */            
        $connector = new NetworkPrintConnector($this->printer_ip, 9100);
        $printer = new Printer($connector);

        /* Print top logo */
        $logo = EscposImage::load($_SERVER["DOCUMENT_ROOT"]."/middleware/print/header_bw.png", false);
        // $logo = EscposImage::load("src/header_bw.png", false);
        $printer -> setJustification(Escpos::JUSTIFY_CENTER);
        $printer -> graphics($logo);

        // add padding left
        $connector->write(Escpos::GS.'L'.intLowHigh(32, 2));

        /* Name of shop */
        $printer -> selectPrintMode(Escpos::MODE_DOUBLE_WIDTH);
        $printer -> text("MHKS, Kamptee\n");
        $printer -> selectPrintMode();
        $printer -> text("63/10/1 Karve Road, Pune - 411008\n");
        $printer -> text("GST No 27ADKFS2744J1ZO\n");
        $printer -> text("Ph No: +91 8329347297");
        $printer -> feed();     
        $printer -> setJustification(Escpos::JUSTIFY_LEFT);


        // $sql0 = "SELECT a.*,b.car_no_plate,d.cust_company,d.cust_f_name,d.cust_l_name,c.id as max FROM `transactions` a JOIN  `cars` b ON a.car_id=b.car_id JOIN `sync` c JOIN `customers` d ON b.car_cust_id = d.cust_id  WHERE a.trans_id =  '".$trans_id."' AND c.table_name = 'transactions';";

        // $result0 = mysqli_query($conn,$sql0);
        // $line = "";

        // while ($row = mysqli_fetch_assoc($result0)) {
        //     $t_id   = $row['transaction_no'];
        //     $vh_no  = str_replace(" ", "-",  $row['car_no_plate']);
        //     $fuel   = $row['fuel'];
        //     $ltr    = $row['liters'];
        //     $rate   = $row['rate'];
        //     $amount = $row['amount'];
        //     $d_name = $row['cust_company'];
        //     if ($d_name == "") {
        //         $d_name = $row['cust_f_name'].' '.$row['cust_l_name'];
        //     }

        //     $line = new item(" ".$vh_no,$fuel,$rate,$ltr,$amount);
        // }


        // $printer -> text("T-ID: ".$t_id." ".$d_name."\n");
        // $printer -> text("--------------------------------------------\n");

        // //header
        // $printer -> setJustification(Escpos::JUSTIFY_LEFT);

        // $header = new item("Vehicle No","Fuel","Rate","Ltr","Amount");

        // $printer ->text($header);
        // $printer -> text("--------------------------------------------\n");
        // $printer -> feed();

        // /* Items */
        // $printer -> setJustification(Escpos::JUSTIFY_LEFT);
        // // line from while loop on top
        // $printer -> text($line);    
        // $printer -> text("--------------------------------------------\n");
        // $printer -> setEmphasis(false);
            
        // /* Footer */
        // $printer -> selectPrintMode();
        // $printer -> feed();
        // $printer -> setJustification(Escpos::JUSTIFY_CENTER);
        // $printer -> text($date);
        // $printer -> feed(2);
        // $printer -> text("Thank you for Visiting MHKS Kamptee\n");
        // $printer -> feed();

        /* Cut the receipt and open the cash drawer */
        $printer -> cut();
        $printer -> pulse();
        $printer -> close();

                
        $response->getBody()->write((string)json_encode('123'));
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


    private function isHostOnline($host, $timeout = 1) {
        exec("ping -c 1 " . $host, $output, $result);
        // print_r($output);
        if ($result == 0){
            // echo "Ping successful!";
            return true;
        }   
        else{
            // echo "Ping unsuccessful!";   
            return false;
        }   
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
