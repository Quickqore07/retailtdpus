<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Onboarding\EmployeeDocument;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\ConnectionException;
use Throwable;
use GuzzleHttp\Cookie\CookieJar;


class TransferFileInAWS extends Command
{
    private const HTTP_TIMEOUT_SECONDS = 120;

    private const HTTP_CONNECT_TIMEOUT_SECONDS = 15;

    private const MAX_PARALLEL_REQUESTS = 25;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transfer:file-in-aws
                            {--offset=0 : Skip first N employees from source list}
                            {--limit=0 : Process only N employees after offset (0 = all)}
                            {--workers=1 : Total number of parallel workers}
                            {--worker-index=0 : Zero-based index of current worker}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $urls = [
        'i9' => 'https://pj.quickob.com/admin/employee/i9/',
        'w4' => 'https://pj.quickob.com/admin/employee/w4/',
        'mw507' => 'https://pj.quickob.com/admin/employee/mw507/',
        'hire' => 'https://pj.quickob.com/admin/employee/hire/',
        'benefit' => 'https://pj.quickob.com/admin/employee/benefit/',
        'direct-deposit' => 'https://pj.quickob.com/admin/employee/direct-deposit/',
        'emergency-contact' => 'https://pj.quickob.com/admin/employee/emergency-contact/',
        'appointment-letter' => 'https://pj.quickob.com/admin/employee/appointment-letter/',
    ];

    private $employeeDocumentsA = [
        '1' => 'U.S. Passport or U.S. Passport Card',
        '2' => 'Permanent Resident Card or Alien Registration Receipt Card (Form I-551)',
        '3' => 'Foreign passport with temporary I-551 stamp or notation on a machine-readable immigrant visa',
        '4' => 'Employment Authorization Document (Form I-766) with photograph',
        '5' => 'For an individual temporarily authorized to work (specific employer due to status/parole)',
        '5a' => 'Foreign passport',
        '5b' => 'Form I-94 or Form I-94A',
        '5b1' => '(1) Same name as passport',
        '5b2' => '(2) Endorsement of status/parole (valid, not expired, no conflicts)',
        '6' => 'Passport from FSM or RMI with Form I-94 or I-94A indicating nonimmigrant admission',
    ];

    private $employeeDocumentsB = [
        '1' => 'Driver\'s license or ID card issued by a State or outlying possession of the U.S. with a photograph or identifying information',
        '2' => 'ID card issued by federal, state, or local agencies with photo or identifying information',
        '3' => 'School ID card with a photograph',
        '4' => 'Voter\'s registration card',
        '5' => 'U.S. Military card or draft record',
        '6' => 'Military dependent\'s ID card',
        '7' => 'U.S. Coast Guard Merchant Mariner Card',
        '8' => 'Native American tribal document',
        '9' => 'Driver\'s license issued by a Canadian government authority',
        '10' => 'School record or report card',
        '11' => 'Clinic, doctor, or hospital record',
        '12' => 'Day-care or nursery school record',
    ];
    private $employeeDocumentsC = [
        '1' => 'Social Security Account Number card, unless the card includes one of the following restrictions',
        '1a' => 'NOT VALID FOR EMPLOYMENT',
        '1b' => 'VALID FOR WORK ONLY WITH INS AUTHORIZATION',
        '1c' => 'VALID FOR WORK ONLY WITH DHS AUTHORIZATION',
        '2' => 'Certification of report of birth issued by the Department of State (Forms DS-1350, FS-545, FS-240)',
        '3' => 'Original or certified copy of birth certificate issued by a State, county, municipal authority, or territory of the United States bearing an official seal',
        '4' => 'Native American tribal document',
        '5' => 'U.S. Citizen ID Card (Form I-197)',
        '6' => 'Identification Card for Use of Resident Citizen in the United States (Form I-179)',
        '7' => 'Employment authorization document issued by the Department of Homeland Security',
    ];

    private $client;

    private CookieJar $cookieJar;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $data = DB::table('onboarding_list_2 as ol')
            ->leftJoin('onboarding_i9_data as i9', 'i9.onboarding_number', '=', 'ol.onboarding_number')
            ->select(
                'ol.onboarding_number as onboarding_number',
                'ol.employeeId as employee_id',
                'ol.id as id',
                'i9.state as state',
                'i9.review_and_verification_documents as review_and_verification_documents'
            )
            ->groupBy('onboarding_number', 'employee_id', 'id', 'state', 'review_and_verification_documents')
            ->get();
        } catch (Throwable $e) {
            Log::error('TransferFileInAWS: failed loading source data', [
                'message' => $e->getMessage(),
            ]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $documentPathLookup = array_flip(DB::table('employee_documents')->pluck('document_path')->toArray());

       

        $offset = max(0, (int) $this->option('offset'));
        $limit = max(0, (int) $this->option('limit'));
        $workers = max(1, (int) $this->option('workers'));
        $workerIndex = (int) $this->option('worker-index');

        if ($workerIndex < 0 || $workerIndex >= $workers) {
            $this->error("Invalid --worker-index {$workerIndex}. It must be between 0 and ".($workers - 1).'.');
            return self::FAILURE;
        }

        $data = $data->values();
        if ($offset > 0) {
            $data = $data->slice($offset)->values();
        }
        if ($limit > 0) {
            $data = $data->take($limit)->values();
        }

        if ($workers > 1) {
            $data = $data->filter(function ($_, int $index) use ($workers, $workerIndex): bool {
                return $index % $workers === $workerIndex;
            })->values();
        }

        $this->info('TransferFileInAWS: selected '.$data->count().' employees (offset='
            .$offset.', limit='.$limit.', workers='.$workers.', worker-index='.$workerIndex.')');

        $employees = Employee::where('workgroup_id', 2)->pluck('id', 'employee_id')->toArray();

        $key = base64_decode('u0xYbXduCp7trMsBa7PBCW8r+T00DAWciCUd33GhuaA='); // remove "base64:"
        $cipher = 'AES-256-CBC';
        
        $encrypter = new Encrypter($key, $cipher);

        $this->cookieJar = new CookieJar();

        // create client
        $this->client = Http::withOptions([
            'cookies' => $this->cookieJar,
        ])->withHeaders([
            'User-Agent' => 'Mozilla/5.0',
        ]);
        
        $loginPage = $this->client->get('https://pj.quickob.com/admin/login');

        /** @var \Illuminate\Http\Client\Response $loginPage */
        
        /** @var \Illuminate\Http\Client\Response $loginPage */
        preg_match('/name="_token" value="(.*?)"/', $loginPage->body(), $matches);
        $token = $matches[1] ?? null;

        $this->client->post('https://pj.quickob.com/admin/login/submit', [
            'email' => 'quickqore7@gmail.com',
            'password' => 'Quickqore@123',
            '_token' => $token,
        ]);

        foreach ($data as $dataKey => $item) {

            // if($dataKey < 395) {
            //     continue;
            // }
            $employee_id = $item->employee_id;
            $employeeDBId = $employees[$employee_id] ?? null;
            $onboarding_number = $item->onboarding_number;
            $review_and_verification_documents = json_decode($item->review_and_verification_documents, true);
            if(!$employeeDBId ) {
                continue;
            }

       

            $ctx = [
                'employee_id' => $employeeDBId,
                'onboarding_number' => $onboarding_number,
            ];
            $employeeStartedAt = microtime(true);
            $keyPath = 'employee-documents/'.$employeeDBId.'/i9.pdf';
            if(isset($documentPathLookup[$keyPath])) {
                continue;
            }

            try {
                $transfers = [];
                $encryptedOnboardingNumber = $encrypter->encrypt($onboarding_number);
                $encryptedEmployeeId = $encrypter->encrypt($employee_id);

                if(!empty($review_and_verification_documents)){
                    foreach($this->urls as $key => $url) {

                        $keyPath = 'employee-documents/'.$employeeDBId.'/'.$key.'.pdf';
                        if(isset($documentPathLookup[$keyPath])) {
                            continue;
                        }

                        $transfers[] = [
                            'url' => $url.$encryptedOnboardingNumber,
                            's3_path' => $keyPath,
                            'log_context' => array_merge($ctx, ['document' => $key, 's3_path' => $keyPath]),
                        ];
                    }
                }else{
                    $keyPath = 'employee-documents/'.$employeeDBId.'/Hire-PDF.pdf';
                    if(isset($documentPathLookup[$keyPath])) {
                        continue;
                    }
                    $transfers[] = [
                        'url' => 'https://pj.quickob.com/admin/employee/hire/'.$encryptedEmployeeId,
                        's3_path' => $keyPath,
                        'log_context' => array_merge($ctx, ['document' => 'Hire-PDF', 's3_path' => $keyPath]),
                    ];
                }
                

                if(isset($review_and_verification_documents['a_selectDocument1']) && $review_and_verification_documents['a_selectDocument1']) {
                    $documentName = $this->employeeDocumentsA[$review_and_verification_documents['a_selectDocumentType1']] ?? null;
                    if ($documentName) {
                        $keyPath = 'employee-documents/'.$employeeDBId.'/'. $documentName;
                        if(isset($documentPathLookup[$keyPath])) {
                            continue;
                        }
                        $transfers[] = [
                            'url' => 'https://pj.quickob.com/public'.$review_and_verification_documents['a_selectDocument1'],
                            's3_path' => $keyPath,
                            'log_context' => array_merge($ctx, ['document' => $documentName, 's3_path' => $keyPath]),
                        ];
                    }
                }


                if(isset($review_and_verification_documents['a_selectDocument2']) && $review_and_verification_documents['a_selectDocument2']) {
                    $documentName = $this->employeeDocumentsA[$review_and_verification_documents['a_selectDocumentType2']] ?? null;
                    if ($documentName) {
                        $keyPath = 'employee-documents/'.$employeeDBId.'/'. $documentName;
                        if(isset($documentPathLookup[$keyPath])) {
                            continue;
                        }
                        $transfers[] = [
                            'url' => 'https://pj.quickob.com/public'.$review_and_verification_documents['a_selectDocument2'],
                            's3_path' => $keyPath,
                            'log_context' => array_merge($ctx, ['document' => $documentName, 's3_path' => $keyPath]),
                        ];
                    }
                }


                if(isset($review_and_verification_documents['a_selectDocument3']) && $review_and_verification_documents['a_selectDocument3']) {
                    $documentName = $this->employeeDocumentsA[$review_and_verification_documents['a_selectDocumentType3']] ?? null;
                    if ($documentName) {
                        $keyPath = 'employee-documents/'.$employeeDBId.'/'. $documentName;
                        if(isset($documentPathLookup[$keyPath])) {
                            continue;
                        }
                        $transfers[] = [
                            'url' => 'https://pj.quickob.com/public'.$review_and_verification_documents['a_selectDocument3'],
                            's3_path' => $keyPath,
                            'log_context' => array_merge($ctx, ['document' => $documentName, 's3_path' => $keyPath]),
                        ];
                    }
                }


                if(isset($review_and_verification_documents['b_selectDocument']) && $review_and_verification_documents['b_selectDocument']) {
                    $documentName = $this->employeeDocumentsB[$review_and_verification_documents['b_selectDocumentType']] ?? null;
                    if ($documentName) {
                        $keyPath = 'employee-documents/'.$employeeDBId.'/'. $documentName;
                        if(isset($documentPathLookup[$keyPath])) {
                            continue;
                        }
                        $transfers[] = [
                            'url' => 'https://pj.quickob.com/public'.$review_and_verification_documents['b_selectDocument'],
                            's3_path' => $keyPath,
                            'log_context' => array_merge($ctx, ['document' => $documentName, 's3_path' => $keyPath]),
                        ];
                    }
                }


                if(isset($review_and_verification_documents['c_selectDocument']) && $review_and_verification_documents['c_selectDocument']) {
                    $documentName = $this->employeeDocumentsC[$review_and_verification_documents['c_selectDocumentType']] ?? null;
                    if ($documentName) {
                        $keyPath = 'employee-documents/'.$employeeDBId.'/'. $documentName;
                        if(isset($documentPathLookup[$keyPath])) {
                            continue;
                        }
                        $transfers[] = [
                            'url' => 'https://pj.quickob.com/public'.$review_and_verification_documents['c_selectDocument'],
                            's3_path' => $keyPath,
                            'log_context' => array_merge($ctx, ['document' => $documentName, 's3_path' => $keyPath]),
                        ];
                    }
                }

                $this->transferUrlsToS3InParallel($transfers);
                
            } catch (Throwable $e) {
                Log::error('TransferFileInAWS: unhandled error for employee batch', array_merge($ctx, [
                    'message' => $e->getMessage(),
                ]));
                $this->warn("Skipped remainder for employeeDBId {$employeeDBId}: {$e->getMessage()}");
            }
            $employeeDurationSeconds = round(microtime(true) - $employeeStartedAt, 2);
            $message = ($dataKey + 1).' - TransferFileInAWS: completed for employeeDBId '
                .$employeeDBId.' in '.$employeeDurationSeconds.'s';
            $this->info($message);
            Log::info('TransferFileInAWS: employee batch duration', array_merge($ctx, [
                'duration_seconds' => $employeeDurationSeconds,
            ]));

        }

        return self::SUCCESS;
    }

    /**
     * GET $url with timeouts, then store body on S3. Logs and returns on failure.
     *
     * @param  array<string, mixed>  $logContext
     */
    private function transferUrlToS3(string $url, string $s3Path, array $logContext = []): void
    {
        try {
            $response = $this->client->timeout(self::HTTP_TIMEOUT_SECONDS)
                ->connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS)
                ->get($url);
            /** @var \Illuminate\Http\Client\Response $response */
            if (! $response->successful()) {
                Log::warning('TransferFileInAWS: HTTP non-success', array_merge($logContext, [
                    'url' => $url,
                    'status' => $response->status(),
                ]));

                return;
            }

            Storage::disk('s3')->put($s3Path, $response->body());

            $documentType = $logContext['document'] == 'i9'  ? EmployeeDocument::DOCUMENT_TYPE_I9_FORM 
                       : ($logContext['document'] == 'w4'  ? EmployeeDocument::DOCUMENT_TYPE_W4_FORM
                       :  EmployeeDocument::DOCUMENT_TYPE_OTHER);

            DB::table('employee_documents')->insert([
                'employee_id' => $logContext['employee_id'],
                'document_name' => $logContext['document'],
                'document_path' => $s3Path,
                'document_type' =>  $documentType,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (ConnectionException $e) {
            Log::error('TransferFileInAWS: HTTP connection/timeout', array_merge($logContext, [
                'url' => $url,
                'message' => $e->getMessage(),
            ]));
        } catch (Throwable $e) {
            Log::error('TransferFileInAWS: transfer or storage failed', array_merge($logContext, [
                'url' => $url,
                's3_path' => $s3Path,
                'message' => $e->getMessage(),
            ]));
        }
    }

    /**
     * @param array<int, array{url: string, s3_path: string, log_context: array<string, mixed>}> $transfers
     */
    private function transferUrlsToS3InParallel(array $transfers): void
    {
        if (empty($transfers)) {
            return;
        }

        foreach (array_chunk($transfers, self::MAX_PARALLEL_REQUESTS) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                $requests = [];

                foreach ($chunk as $index => $transfer) {
                    $requests[$index] = $pool
                        ->as((string) $index)
                        ->withOptions(['cookies' => $this->cookieJar])
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                        ->timeout(self::HTTP_TIMEOUT_SECONDS)
                        ->connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS)
                        ->get($transfer['url']);
                }

                return $requests;
            });

            foreach ($chunk as $index => $transfer) {
                $response = $responses[(string) $index] ?? null;
                if (! $response instanceof Response) {
                    Log::warning('TransferFileInAWS: empty pooled HTTP response', array_merge($transfer['log_context'], [
                        'url' => $transfer['url'],
                    ]));
                    continue;
                }

                $this->persistTransferResponse($response, $transfer['url'], $transfer['s3_path'], $transfer['log_context']);
            }
        }
    }

    /**
     * @param array<string, mixed> $logContext
     */
    private function persistTransferResponse(Response $response, string $url, string $s3Path, array $logContext): void
    {
        try {
            if (! $response->successful()) {
                Log::warning('TransferFileInAWS: HTTP non-success', array_merge($logContext, [
                    'url' => $url,
                    'status' => $response->status(),
                ]));

                return;
            }

            Storage::disk('s3')->put($s3Path, $response->body());

            $documentType = $logContext['document'] == 'i9'  ? EmployeeDocument::DOCUMENT_TYPE_I9_FORM
                : ($logContext['document'] == 'w4'  ? EmployeeDocument::DOCUMENT_TYPE_W4_FORM
                :  EmployeeDocument::DOCUMENT_TYPE_OTHER);

            DB::table('employee_documents')->insert([
                'employee_id' => $logContext['employee_id'],
                'document_name' => $logContext['document'],
                'document_path' => $s3Path,
                'document_type' => $documentType,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('TransferFileInAWS: transfer or storage failed', array_merge($logContext, [
                'url' => $url,
                's3_path' => $s3Path,
                'message' => $e->getMessage(),
            ]));
        }
    }
}
