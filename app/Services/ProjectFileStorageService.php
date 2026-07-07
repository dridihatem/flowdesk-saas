<?php

namespace App\Services;

use App\Enums\ProjectFileCategory;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectTask;
use App\Models\ProjectTaskFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProjectFileStorageService
{
    /**
     * Laravel validation rule fragment for max kilobytes (single file).
     */
    public function maxFileRule(): string
    {
        return 'max:'.max(1, $this->maxFileKb());
    }

    public function maxFileKb(): int
    {
        return (int) config('flowdesk.project_files.max_file_kb', 12288);
    }

    public function maxStorageBytes(): int
    {
        $mb = (int) config('flowdesk.project_files.max_storage_mb_per_project', 512);

        return max(1, $mb) * 1024 * 1024;
    }

    public function mimeRule(): string
    {
        return 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,gif,webp,zip,ppt,pptx';
    }

    public function projectStorageUsedBytes(Project $project): int
    {
        $projectFiles = (int) ProjectFile::query()
            ->withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->sum('size');

        $taskFiles = (int) ProjectTaskFile::query()
            ->withoutGlobalScopes()
            ->whereHas('task', fn ($q) => $q->where('project_id', $project->id))
            ->sum('size');

        return $projectFiles + $taskFiles;
    }

    public function assertRoomForUpload(Project $project, int $additionalBytes): void
    {
        $used = $this->projectStorageUsedBytes($project);
        $max = $this->maxStorageBytes();
        if ($used + $additionalBytes > $max) {
            throw ValidationException::withMessages([
                'file' => [__('This upload would exceed the storage limit for this project (:used / :max MB).', [
                    'used' => number_format($used / 1024 / 1024, 1),
                    'max' => number_format($max / 1024 / 1024, 0),
                ])],
            ]);
        }
    }

    public function storeForProject(Project $project, UploadedFile $file, ProjectFileCategory $category, bool $vault = false): ProjectFile
    {
        $this->assertRoomForUpload($project, (int) $file->getSize());

        // Vault files live on the private disk: no public URL, download is
        // permission-checked (or via a share link, optionally password-protected).
        $disk = $vault ? 'local' : 'public';
        $dir = ($vault ? 'vault/' : '').'project-files/'.$project->id;

        $path = $file->store($dir, $disk);
        $thumbPath = $vault ? null : $this->tryCreateThumbnail($file, $path, $disk);

        return ProjectFile::query()->create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'category' => $category->value,
            'is_vault' => $vault,
            'disk' => $disk,
            'path' => $path,
            'thumb_path' => $thumbPath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function storeForTask(Project $project, ProjectTask $task, UploadedFile $file, ProjectFileCategory $category): ProjectTaskFile
    {
        $this->assertRoomForUpload($project, (int) $file->getSize());

        $path = $file->store('project-task-files/'.$project->id, 'public');
        $thumbPath = $this->tryCreateThumbnail($file, $path, 'public');

        return ProjectTaskFile::query()->create([
            'company_id' => $project->company_id,
            'project_task_id' => $task->id,
            'category' => $category->value,
            'disk' => 'public',
            'path' => $path,
            'thumb_path' => $thumbPath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Create a JPEG thumbnail next to the original; returns storage-relative path or null.
     */
    private function tryCreateThumbnail(UploadedFile $file, string $storedPath, string $disk): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $mime = strtolower((string) $file->getMimeType());
        if (! str_starts_with($mime, 'image/') || str_contains($mime, 'svg')) {
            return null;
        }

        $full = Storage::disk($disk)->path($storedPath);
        if (! is_file($full) || ! is_readable($full)) {
            return null;
        }

        $src = match (true) {
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => @imagecreatefromjpeg($full),
            str_contains($mime, 'png') => @imagecreatefrompng($full),
            str_contains($mime, 'gif') => @imagecreatefromgif($full),
            str_contains($mime, 'webp') => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($full) : false,
            default => false,
        };

        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 1 || $h < 1) {
            imagedestroy($src);

            return null;
        }

        $maxW = max(64, (int) config('flowdesk.project_files.thumb_max_width', 360));
        if ($w <= $maxW) {
            $newW = $w;
            $newH = $h;
        } else {
            $newW = $maxW;
            $newH = (int) max(1, round($h * ($maxW / $w)));
        }

        $dst = imagecreatetruecolor($newW, $newH);
        if ($dst === false) {
            imagedestroy($src);

            return null;
        }

        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);

        $dir = trim(dirname($storedPath), '/');
        $base = pathinfo($storedPath, PATHINFO_FILENAME);
        $thumbRelative = ($dir !== '' ? $dir.'/' : '').'thumbs/'.$base.'_thumb.jpg';

        Storage::disk($disk)->makeDirectory(dirname($thumbRelative));

        ob_start();
        $quality = max(40, min(95, (int) config('flowdesk.project_files.thumb_jpeg_quality', 82)));
        imagejpeg($dst, null, $quality);
        $binary = ob_get_clean();
        imagedestroy($dst);

        if ($binary === false || $binary === '') {
            return null;
        }

        Storage::disk($disk)->put($thumbRelative, $binary);

        return $thumbRelative;
    }
}
