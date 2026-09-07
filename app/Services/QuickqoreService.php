<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class QuickqoreService
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

    public function handlePurchase(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
        $response = $this->quickqoreHttpClient()->post($this->apiKey . '/dailypurchase', $data);
        info('Purchase Response: '.json_encode($response->json()));
        if (!$response->successful()) {
            $error = json_decode($response->body(), true);
            throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
        }
        return $response->json();
        } catch (\Exception $e) {
            // $response = $this->quickqoreHttpClient()->post($this->apiKey . '/dailypurchase-delete', ['purchase_id' => $data['purchase_id']]);
            // info('Purchase Delete Response: '.json_encode($response->json()));
            info('Purchase Error: '.json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
    public function handleDailySale(array $data)
    {
        try {
        /** @var \Illuminate\Http\Client\Response $response */
        $response = $this->quickqoreHttpClient()->post($this->apiKey . '/dailysales', $data);
        info('Daily Sale Response: '.json_encode($response->json()));
        if (!$response->successful()) {
            $error = json_decode($response->body(), true);
            throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
        }
        return $response->json();
        } catch (\Exception $e) {
            // $response = $this->quickqoreHttpClient()->delete($this->apiKey . '/dailysales-delete', ['sales_id' => $data['sales_id']]);
            // info('Daily Sale Delete Response: '.json_encode($response->json()));

            info('Daily Sale Error: '.json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }

    public function handlePayrollCheck(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/payroll-checks', $data);
            info('Payroll Check Response: '.json_encode($response->json()));
            if (!$response->successful()) {
                // $error = json_decode($response->body(), true);
                // throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }
            return $response->json();
        } catch (\Exception $e) {
            $this->quickqoreHttpClient()->post($this->apiKey . '/payroll-checks-delete', ['payroll_check_ids' => array_column($data, 'id')]);
            info('Payroll Check Error: '.json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }

    public function handleFinanceReport(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/finance-reports', $data);
            if (!$response->successful()) {
                $error = json_decode($response->body(), true);
                throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }
            return $response->json();
        } catch (\Exception $e) {
            info('Finance Report Error: '.json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }

    public function handleDetailFinanceReport(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/detailed-finance-reports', $data);
            if (!$response->successful()) {
                $error = json_decode($response->body(), true);
                throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }
            return $response->json();
        } catch (\Exception $e) {
            info('Detail Finance Report Error: '.json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }
    public function handleStoreWiseFinanceReport(array $data)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->quickqoreHttpClient()->post($this->apiKey . '/store-wise-finance-reports', $data);
            if (!$response->successful()) {
                $error = json_decode($response->body(), true);
                throw new \RuntimeException(isset($error['error']) ? $error['error'] : 'Quickqore API request failed');
            }
            return $response->json();
        } catch (\Exception $e) {
            info('Store Wise Finance Report Error: '.json_encode($e->getMessage()));
            throw new \RuntimeException($e->getMessage() != null ? $e->getMessage() : 'Quickqore API request failed');
        }
    }



}