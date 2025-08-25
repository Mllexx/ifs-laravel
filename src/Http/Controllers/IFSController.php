<?php

namespace Mllexx\IFS\Http\Controllers;

use Illuminate\Http\Request;
use Mllexx\IFS\Http\IFSClient;
use Mllexx\IFS\Services\CustomerService;
use Mllexx\IFS\Services\InvoiceService;

class IFSController
{
    public function index()
    {
        $client = new IFSClient();
        #$response = $client->get('/CustomersHandling.svc/CustomerInfoSet');
        $customerService = new CustomerService($client);
        //$invoiceService = new InvoiceService($client,'Instant');
        $invoiceService = new InvoiceService($client);
        //$response = $invoiceService->create($this->invoicePayload);
        $response = $invoiceService->list([],16);
        dd($response);
        #
        //foreach($response as $customer){
        //    dd($customer);
        //}
        /*
        return response()->json([
            'message' => 'IFS Laravel Package',
        ]);
        */
    }
    
}
