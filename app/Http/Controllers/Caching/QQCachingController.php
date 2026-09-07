<?php

namespace App\Http\Controllers\Caching;

use App\Http\Controllers\Controller;
use App\Models\Caching\QQCaching;
use App\Services\Quickqore\QuickqoreService;

class QQCachingController extends Controller
{
    public function setCompaniesCacheAction()
    {
        try {
            $quickqoreService = new QuickqoreService();
            $response = $quickqoreService->getCompaniesList();
            if($response['success']){
                    QQCaching::setCache('companies', $response['data']);
            }
            return to_json(['success' => true, 'message' => 'Companies cache set successfully']);
        } catch (\Throwable $th) {
            return to_json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function getCompaniesCacheAction()
    {
        $caching = QQCaching::getCache('companies');
        return to_json(['success' => true, 'data' => $caching]);
    }
}