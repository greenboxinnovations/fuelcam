<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Slim\Psr7\Factory\ResponseFactory;

use Slim\Psr7\Factory\StreamFactory;

use Psr\Http\Message\UploadedFileInterface;

return function (App $app) {

    $app->get('/test', function(Request $request, Response $response) {
        $ret_data = array("otp" => "working");
        $response->getBody()->write((string)json_encode($ret_data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    });

    $app->get('/test/{id}', function(Request $request, Response $response) {
        $ret_data = array("otp" => "id");
        $response->getBody()->write((string)json_encode($ret_data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    });


    $app->post('/login', \App\LoginController::class);

    $app->post('/rates', \App\RatesController::class);

    $app->post('/scan_car',\App\ScanCarController::class);

    $app->post('/scan_pump',\App\SnapPhotoController::class);

    $app->post('/transactions', \App\TransactionsController::class);

    $app->get('/receipts/{r_num}', \App\ReceiptsController::class);
    
    $app->get('/customers', \App\CustomersController::class);

    $app->get('/no_plate/{no_plate}', \App\VehiclePlateController::class);

    $app->get('/latest_rate', \App\LatestRateController::class);

    // code doesnt not consider curl request failure
    // exe/request_otp.php
    // exe/verify_otp.php



    /*
    // TODO
    $app->post('/otp_request', \App\OtpRequest::class);


    

    $app->post('/otp_verify', \App\OtpVerifyController::class);

    $app->post('/ref_verify', \App\RefVerifyController::class);


    $app->post('/generate_codes', \App\GenerateCodesController::class);


    // pump auth to be created and added here
    $app->get('/pump_scan', \App\PumpScanController::class);
    $app->post('/pending_trans_completed', \App\CompletedTransactionController::class);
    $app->get('/post_video_check', \App\PostVideoCheckController::class);

    $app->post('/post_new_car', \App\NewCarController::class);

    $app->post('/post_video', \App\PostVideoController::class);

    // add auth here
    //$app->get('/cars_pending', \App\CarAndPendingController::class);


    // group has AuthCheck attached to each request
    $app->group('', function (Group $group) {

        $group->post('/new_transaction', \App\NewTransactionController::class);

        $group->get('/cars_pending', \App\CarAndPendingController::class);

        $group->get('/ad', function (Request $request, Response $response) {

            $id = $request->getAttribute('user_id');

            $response->getBody()->write($id);
            return $response;
        });
    })->add(\App\AuthCheck::class);



    $app->get('/view_video', function (Request $request, Response $response) {

        set_time_limit(0);

        // $clipid = $args['clipid'];
        // $clip = "/uploads/lgurT6xW72.mp4";
        //$directory = dirname(__DIR__) . '/uploads';
        // $file = dirname(__DIR__) . '/uploads/lgurT6xW72.mp4';
        $file = dirname(__DIR__) . '/uploads/1DyF5zWYnJ.mp4';
        // $openFile = fopen($file, 'rb');
        // $stream = new Slim\Http\Stream($openFile);

        /*
        $response = $response->withHeader('Content-type', 'video/mp4');


        // return $response->withBody((new StreamFactory())->createStreamFromFile($file));
        return $response->withBody((new StreamFactory())->createStreamFromFile($file));
        // createStream
        // createStreamFromResource
        


        set_time_limit(0);

        header('Content-Type: video/mp4');
        header('Content-Length: ' . filesize($file));

        $handle = fopen($file, "rb");
        while (!feof($handle)) {
            echo fread($handle, 8192);
            ob_flush();
            flush();
        }
        fclose($handle);
        exit(0);
    });



    // FCM test
    $app->get('/paytm_test', \App\TestPayments::class);


    $app->post('/file_test', function (
        Request $request,
        Response $response
    ) {

        // var_dump($request->getUploadedFiles()['test']);
        $uploadedFiles = $request->getUploadedFiles();

        // $directory = dirname(__DIR__, 1);
        $directory = dirname(__DIR__) . '/uploads';

        $success_file = -99;

        // if (empty($uploadedFiles['test'])) {
        //     $file = "isepmty";
        // } else {
        //     $file = $uploadedFiles['test'];

        //     if ($file->getError() === UPLOAD_ERR_OK) {
        //         $success_file = moveUploadedFile($directory, $file);
        //     }
        // }


        // echo exec('whoami');

        // HTTP response
        $otp_data = array("fcm" => $success_file);
        $response->getBody()->write((string)json_encode($directory));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    });
    */
};

