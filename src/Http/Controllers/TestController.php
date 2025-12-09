<?php

namespace Mllexx\IFS\Http\Controllers;

use Illuminate\Http\Request;
use Mllexx\IFS\Http\IFSClient;
use Mllexx\IFS\Services\CustomerService;
use Mllexx\IFS\Services\InvoiceService;
use Mllexx\IFS\Services\SalesObjectService;
use Mllexx\IFS\Services\CurrencyInfoService;

class TestController
{
    public $invoicePayload = [
        "Company" => "GBHL",
        "Identity" => "GB-GIL01K",
        "PartyType" => "Customer",
        "InvoiceDate" => "2025-03-03",
        "DueDate" => "2025-03-03",
        "Creator" => "INSTANT_INVOICE_API",
        "CurrRate" => 1,
        "InvoiceType" => "INSTINV",
        "PayTermId" => "0",
        "CreationDate" => "2025-03-03T09:57:35Z",
        "Currency" => "KES",
        "Sent" => "FALSE",
        "MultiCompanyInvoice" => "FALSE",
        "PayTermBaseDate" => "2025-03-03",
        "AdvInv" => false,
        "ProposalExist" => "FALSE",
        "PostPrelTaxWith" => true,
        "PrepayBasedInv" => false,
        "UseProjAddressForTax" => false,
        "SiiProposal" => false,
        "OperationalKey" => "NoValue",
        "OutInvVouDateBase" => "InvoiceDate",
        "OutInvCurrRateBase" => "InvoiceDate",
        "TaxSellCurrRateBase" => "InvoiceDate",
        "AboveTaxControlLimit" => false,
        "EinvoiceSent" => false,
        "UseDeliveryInvAddress" => false,
        "ExcludePostingAuth" => false,
        "CurrDifferenceInvoice" => false,
        "DigitalInvoice" => false,
        "TaxAdjustmentInvoice" => false,
        "Collect" => "FALSE",
        "Cash" => "FALSE",
        "IntAllowed" => "TRUE",
        "AffBaseLedgPost" => "TRUE",
        "TaxCurrRate" => 1,
        "DivFactor" => 1,
        "TaxLiability" => "TAX",
        "InvoiceAddressId" => "1",
        "SupplyCountry" => "KE",
        "DeliveryCountry" => "KE",
        "ParallelCurrRate" => 101.3
    ];
    
    public function index()
    {
        $client = new IFSClient();
        #$response = $client->get('/CustomersHandling.svc/CustomerInfoSet');
        //$customerService = new CustomerService($client);
        //$invoiceService = new InvoiceService($client,'Instant');
        //$invoiceService = new InvoiceService($client,);
        //$response = $invoiceService->create($this->invoicePayload);
        //$response = $invoiceService->list([],3);
        //dd($response);
        //$salesObjectService = new SalesObjectService();
        //$response = $salesObjectService->getSalesObjects();
        $payload = [
            'Company' => "'GBHL'",
            //'Identity' => 'GB-GIL01K',
            'Identity' => "'GB-MOM01U'",
            'TransCurrency' => "'KES'",
            'InvoiceDate' => now()->format('Y-m-d'),
        ];
        $currencyInfoService = new CurrencyInfoService();
        $response = $currencyInfoService->getCurrencyInfo($payload);
        dd($response);
    }
}
