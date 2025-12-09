<?php

namespace Mllexx\IFS\Services;

use Mllexx\IFS\Http\IFSClient;
use Mllexx\IFS\Exceptions\IFSException;
use Illuminate\Support\Facades\Log;

class CurrencyInfoService
{
    protected $client;

    protected $endpoints = [
        'currencyInfo' => '/InstantInvoiceHandling.svc/FetchCurrencyInfo(<queryParams>)',
    ];

    protected $queryParams = [
        'Company' => null,
        'PartyType' => "IfsApp.InstantInvoiceHandling.PartyType'Customer'",
        'Identity' => null,
        'TransCurrency' => null,
        'CurrencyType' => null,//use defaults
        'Creator' => "'INSTANT_INVOICE_API'",
        'AdvanceInvoice' => false,
        'InvoiceDate' => null,// e.g '2025-03-03',
        'DeliveryDate' => null,// use defaults
        'VoucherDate' => null,// use defaults
        'ArrivalDate' => null,// use defaults
        'CustomsDeclDate' => null,// use defaults
        'OutInvCurrRateBase' => "IfsApp.InstantInvoiceHandling.BaseDateOutgoingBase'InvoiceDate'",
        'TaxSellCurrRateBase' => "IfsApp.InstantInvoiceHandling.BaseDateOutgoingBase'InvoiceDate'",
        'IncInvCurrRateBase' => null, //use defaults
        'TaxBuyCurrRateBase' => null, //use defaults
    ];

    public function __construct()
    {
        $this->client = new IFSClient();
    }

    public function getCurrencyInfo($params)
    {
        Log::info("Fetching currency info for currency code [{$params['TransCurrency']}]");
        $endpoint = $this->_addQueryParamsToEndpoint(
            $this->endpoints['currencyInfo'],
            $this->_compileParams($params)
        );
        Log::info("Calling the enpoint [$endpoint]");
        $response = $this->client->get($endpoint);
        if($response->isSuccessful()){
            Log::info("Currency info fetched successfully");
            return $response->getData();
        }else{
            Log::error("Failed to fetch currency info");
            return [];
        }
    }

    private function _compileParams($params)
    {
        $compiledParams = [];
        foreach ($this->queryParams as $key => $defaultValue) {
            if (isset($params[$key])) {
                $compiledParams[$key] = $params[$key];
            } else {
                $compiledParams[$key] = $defaultValue;
            }
        }
        return $compiledParams; 
    }

    private function _addQueryParamsToEndpoint($endpoint, $params)
    {
        $queryString = http_build_query($params);
        return str_replace('<queryParams>', $queryString, $endpoint);
    }
}