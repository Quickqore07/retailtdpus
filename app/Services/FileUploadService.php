<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /**
     * Default disk for uploads: S3 when configured, otherwise public (storage/app/public) so files are web-accessible.
     */
    protected function uploadDisk(): string
    {
        $default = config('filesystems.default');
        return $default === 's3' ? 's3' : 'public';
    }

    /**
     * Store an uploaded file. Uses AWS S3 when FILESYSTEM_DISK=s3, otherwise stores locally (public disk).
     *
     * @param  UploadedFile  $file
     * @param  string  $directory  Directory under the disk (e.g. 'onboarding-work-permits')
     * @param  string|null  $visibility  'public' or 'private'. For S3 uses same; for local public disk is public.
     * @param  string|null  $filename  Optional stored filename (e.g. invoice number). Defaults to uniqid + original name.
     * @return array{path: string, url: string}  Stored path (save in DB) and public URL for display.
     */
    public function store(UploadedFile $file, string $directory, ?string $visibility = 'public', ?string $filename = null): array
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('File upload failed: '.$file->getErrorMessage());
        }

        $disk = $this->uploadDisk();
        Storage::disk($disk)->makeDirectory($directory);
        $filename = $this->resolveFilename($file, $filename);

        $path = Storage::disk($disk)->put(
            $directory.'/'.$filename,
            file_get_contents($file->getRealPath()),
            'public'
        );
        
        // 'visibility' => $visibility ?? 'public',
        
        if ($path === false) {
            throw new \RuntimeException('Failed to store file on disk ['.$disk.']. Check permissions and disk configuration.');
        }
        $path = $directory.'/'.$filename;
        $url = $this->url($path);

        return [
            'path' => $path,
            'url' => $url,
        ];
    }

    /**
     * Build public URL for a stored path. Uses the same disk as store() (S3 or public).
     */
    public function url(string $path): string
    {
        if (empty($path)) {
            return '';
        }
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        /** @var \Illuminate\Contracts\Filesystem\FilesystemAdapter $adapter */
        $adapter = Storage::disk($disk);
        if ($disk === 's3') {
            return $adapter->temporaryUrl(
                $path,
                now()->addMinutes(30)
            );
        }
        return $adapter->url($path);
    }

    /**
     * Delete a file by path from the upload disk.
     */
    public function delete(string $path): bool
    {
        if (empty($path)) {
            return false;
        }
        return Storage::disk($this->uploadDisk())->delete($path);
    }

    private function resolveFilename(UploadedFile $file, ?string $filename): string
    {
        if (empty($filename)) {
            return uniqid().'_'.$file->getClientOriginalName();
        }

        $filename = basename(str_replace(['\\', '/'], '', $filename));
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'file';
        if (!pathinfo($filename, PATHINFO_EXTENSION)) {
            $extension = $file->getClientOriginalExtension() ?: 'pdf';
            $filename .= '.'.$extension;
        }

        return $filename;
    }
}
