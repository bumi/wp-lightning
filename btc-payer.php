<?php

require_once 'vendor/autoload.php';

use BTCPayServer\Client\ApiKey;
use BTCPayServer\Client\Invoice;
use BTCPayServer\Util\PreciseNumber;

class BTCPay
{

  protected $apiKey = '';
  protected $host = '';
  protected $client;
  protected $storeId = '';

  public function __construct($host, $apiKey, $storeId)
  {
    $this->host = $host;
    $this->apiKey = $apiKey;
    $this->storeId = $storeId;
  }

  public function init()
  {
    try {
      $this->client = new ApiKey($this->host, $this->apiKey);
    } catch (\Throwable $e) {
      echo "Error: " . $e->getMessage();
    }
  }

  public function isConnectionValid(): bool
  {
    return !empty($this->client) && !empty($this->client->getCurrent());
  }

  public function getBalance()
  {
    return 0;
  }


  public function addInvoice($invoice): array
  {
    try {
      $client = new Invoice($this->host, $this->apiKey);
      $invoice =  $client->createInvoice(
        $this->storeId,
        $invoice['currency'] ?? '',
        PreciseNumber::parseString($invoice['value']),
        '',
        ''
      );
      $data = $invoice->getData();
      return [
        'r_hash' => $data['id'],
        'payment_request' => $data['checkoutLink']
      ];
    } catch (\Throwable $e) {
      echo "Error: " . $e->getMessage();
    }
    return [];
  }


  public function getInvoice($checkingId): array
  {
    try {
      $client = new Invoice($this->host, $this->apiKey);
      $invoice = $client->getInvoice($this->storeId, $checkingId);
      $data = $invoice->getData();
      return [
        'r_hash' => $data['id'],
        'payment_request' => $data['checkoutLink'],
        'settled' => $invoice->isFullyPaid(),
      ];
    } catch (\Throwable $e) {
      echo "Error: " . $e->getMessage();
    }
    return [];
  }

  public function isInvoicePaid($checkingId): bool
  {
    try {
      $client = new Invoice($this->host, $this->apiKey);
      $invoice = $client->getInvoice($this->storeId, $checkingId);
      return $invoice->isFullyPaid();
    } catch (\Throwable $e) {
      echo "Error: " . $e->getMessage();
    }
    return false;
  }
}
