<?php

namespace App\Services\Quickqore;

use Illuminate\Support\Facades\Http;

class QuickqoreInvoiceService
{
    private $apiKey;
    private $securityKey;

    public function __construct()
    {
        $this->apiKey = env('QUICKQORE_API_URL');
        $this->securityKey = env('QUICKQORE_SECURITY_KEY');
    }

    public function quickqoreHttpClient()
    {
        return Http::acceptJson()->withHeaders([
            'X-Security-Key' => $this->securityKey,
        ]);
    }

    public function getVendors(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/vendors', $data);
            // info('Get Vendors Response: '.json_encode($response->json()));
            if (!$response->successful()) {
                $error = json_decode($response->body(), true);
                throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }
            return $response->json();
        } catch (\Exception $e) {
            // $response = $this->quickqoreHttpClient()->post($this->apiKey . '/dailypurchase-delete', ['purchase_id' => $data['purchase_id']]);
            // info('Purchase Delete Response: '.json_encode($response->json()));
            info('Get Vendors Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }

    /**
     * Create a vendor in Quickqore. External path must match the Quickqore service contract.
     */
    public function storeVendor(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/vendors/store', $data);
            if (!$response->successful()) {
                $error = json_decode($response->body(), true);
                throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }

            return $response->json();
        } catch (\Exception $e) {
            info('Store Vendor Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }

    public function getExpenseLedgers(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/expense-ledgers', $data);
            // info('Get Expense Ledgers Response: '.json_encode($response->json()));
            if (!$response->successful()) {
                $error = json_decode($response->body(), true);
                throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }
            return $response->json();
        } catch (\Exception $e) {
            // $response = $this->quickqoreHttpClient()->post($this->apiKey . '/dailypurchase-delete', ['purchase_id' => $data['purchase_id']]);
            // info('Purchase Delete Response: '.json_encode($response->json()));
            info('Get Expense Ledgers Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
    public function getBanks(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/banks', $data);
            // info('Get Banks Response: '.json_encode($response->json()));
            if (!$response->successful()) {
                $error = json_decode($response->body(), true);
                throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }
            return $response->json();
        } catch (\Exception $e) {
            // $response = $this->quickqoreHttpClient()->post($this->apiKey . '/dailypurchase-delete', ['purchase_id' => $data['purchase_id']]);
            // info('Purchase Delete Response: '.json_encode($response->json()));
            info('Get Banks Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
    public function getSigns(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/signs', $data);
            // info('Get Signs Response: '.json_encode($response->json()));
            if (!$response->successful()) {
                $error = json_decode($response->body(), true);
                throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }
            return $response->json();
        } catch (\Exception $e) {
            // $response = $this->quickqoreHttpClient()->post($this->apiKey . '/dailypurchase-delete', ['purchase_id' => $data['purchase_id']]);
            // info('Purchase Delete Response: '.json_encode($response->json()));
            info('Get Signs Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
    public function getLatestCheckNumber(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/latest-check-number', $data);
            // info('Get Latest Check Number Response: '.json_encode($response->json()));
            if (!$response->successful()) {
                $error = json_decode($response->body(), true);
                throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }
            return $response->json();
        } catch (\Exception $e) {
            // $response = $this->quickqoreHttpClient()->post($this->apiKey . '/dailypurchase-delete', ['purchase_id' => $data['purchase_id']]);
            // info('Purchase Delete Response: '.json_encode($response->json()));
            info('Get Latest Check Number Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
    public function getBusinessUnits(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/business-units', $data);
            // info('Get Business Units Response: '.json_encode($response->json()));
            if (!$response->successful()) {
                $error = json_decode($response->body(), true);
                info('Get Business Units Error: ' . json_encode($error));
                throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }
            return $response->json();
        } catch (\Exception $e) {
            // $response = $this->quickqoreHttpClient()->post($this->apiKey . '/dailypurchase-delete', ['purchase_id' => $data['purchase_id']]);
            // info('Purchase Delete Response: '.json_encode($response->json()));
            info('Get Business Units Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }

    public function storeApInvoice(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/ap-invoices', $data);
            // info('Store AP Invoice Response: '.json_encode($response->json()));
            return $response->json();
        } catch (\Exception $e) {
            info('Store AP Invoice Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
    public function updateApInvoice(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->put($this->apiKey . '/ap-invoices', $data);
            // info('Update AP Invoice Response: '.json_encode($response->json()));
            return $response->json();
        } catch (\Exception $e) {
            info('Update AP Invoice Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
    public function approveApInvoice(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/ap-invoices/approve', $data);
            // info('Approve AP Invoice Response: '.json_encode($response->json()));
            return $response->json();
        } catch (\Exception $e) {
            info('Approve AP Invoice Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
    public function deleteApInvoice(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->delete($this->apiKey . '/ap-invoices', $data);
            // info('Delete AP Invoice Response: '.json_encode($response->json()));
            return $response->json();
        } catch (\Exception $e) {
            info('Delete AP Invoice Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }

    public function handleApPayment(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/ap-payment', $data);
            // info('Handle AP Payment Response: '.json_encode($response->json()));
            return $response->json();
        } catch (\Exception $e) {
            info('Approve AP Payment Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
    public function deleteApPayment(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->delete($this->apiKey . '/ap-payment', $data);
            // info('Delete AP Payment Response: '.json_encode($response->json()));
            return $response->json();
        } catch (\Exception $e) {
            info('Delete AP Payment Error: ' . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }

    public function storeArPayment(array $data)
    {
        return $this->callArPayment('post', '/ar-payment', $data, 'Store AR Payment Error: ');
    }

    public function updateArPayment(array $data)
    {
        return $this->callArPayment('put', '/ar-payment', $data, 'Update AR Payment Error: ');
    }

    public function deleteArPayment(array $data)
    {
        return $this->callArPayment('delete', '/ar-payment', $data, 'Delete AR Payment Error: ');
    }

    public function storeArCustomer(array $data)
    {
        return $this->callArCustomer('post', '/ar-customer', $data, 'Store AR Customer Error: ');
    }

    public function updateArCustomer(array $data)
    {
        return $this->callArCustomer('put', '/ar-customer', $data, 'Update AR Customer Error: ');
    }

    private function callArCustomer(string $method, string $path, array $data, string $logPrefix)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->asJson()->{$method}($this->apiKey . $path, $data);
            $json = $response->json();
            if (!$response->successful() || empty($json['success'])) {
                $message = $json['message'] ?? $json['error'] ?? 'Quickqore API request failed';
                throw new \RuntimeException($message);
            }

            return $json;
        } catch (\RuntimeException $e) {
            info($logPrefix . json_encode($e->getMessage()));
            throw $e;
        } catch (\Exception $e) {
            info($logPrefix . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }

    private function callArPayment(string $method, string $path, array $data, string $logPrefix)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $client = $this->quickqoreHttpClient()->asJson();
            if ($method === 'delete') {
                $response = $client->send('DELETE', $this->apiKey . $path, ['json' => $data]);
            } else {
                $response = $client->{$method}($this->apiKey . $path, $data);
            }
            $json = $response->json();
            if (!$response->successful() || empty($json['success'])) {
                $message = $json['message'] ?? $json['error'] ?? 'Quickqore API request failed';
                throw new \RuntimeException($message);
            }

            return $json;
        } catch (\RuntimeException $e) {
            info($logPrefix . json_encode($e->getMessage()));
            throw $e;
        } catch (\Exception $e) {
            info($logPrefix . json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
}
