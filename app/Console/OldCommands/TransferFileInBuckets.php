<?php

namespace App\Console\Commands;

use App\Models\Onboarding\EmployeeDocument;
use Aws\CommandPool;
use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Exception;

class TransferFileInBuckets extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transfer:file-in-buckets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer files in buckets';

    private $array = [
        '4168',
        '4370',
        '4542',
        '4694',
        '4705',
        '4712',
        '4733',
        '4772',
        '4983',
        '5020',
        '5134',
        '5261',
        '5535',
        '5593',
        '5609',
        '5759',
        '5911',
        '5928',
        '5929',
        '5955',
        '5964',
        '5973',
        '6058',
        '6074',
        '6116',
        '6128',
        '6135',
        '6210',
        '6238',
        '6241',
        '6272',
        '6318',
        '6353',
        '6370',
        '6391',
        '6406',
        '6509'
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $employeeDocuments = EmployeeDocument::whereIn('employee_id', $this->array)->pluck('document_path')->toArray();

            $s3 = new S3Client([
                'region' => env('AWS_DEFAULT_REGION'),
                'version' => 'latest',
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);
            
            $commands = [];
            
            foreach ($employeeDocuments as $document) {
                $commands[] = $s3->getCommand('CopyObject', [
                    'Bucket' => 'quick-ob-production',
                    'Key' => $document,
                    'CopySource' => env('AWS_BUCKET') . '/' . $document,
                ]);
            }
            
            $pool = new CommandPool($s3, $commands, [
                'concurrency' => 20,
                'fulfilled' => function ($result, $index) use ($employeeDocuments) {
                    $this->info($index . ' - Copied: ' . $employeeDocuments[$index]);
                },
                'rejected' => function ($reason, $index) use ($employeeDocuments) {
                    $this->error($index . ' - Failed: ' . $employeeDocuments[$index]);
                    $this->error($reason);
                },
            ]);
            
            $promise = $pool->promise();
            $promise->wait();
        } catch (Exception $e) {
            $this->error('Error transferring files in buckets: ' . $e->getMessage());
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
