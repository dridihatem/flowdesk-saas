<?php

namespace App\Http\Controllers;

use App\Models\ProjectFileShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SharedFileController extends Controller
{
    public function show(string $token): View
    {
        $share = $this->findValidShare($token);

        return view('share.file', [
            'share' => $share,
            'file' => $share->file,
        ]);
    }

    public function download(Request $request, string $token): StreamedResponse
    {
        $share = $this->findValidShare($token);
        $file = $share->file;

        if ($share->hasPassword()) {
            $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (! Hash::check((string) $request->input('password'), (string) $share->password_hash)) {
                throw ValidationException::withMessages([
                    'password' => [__('project_vault_share_wrong_password')],
                ]);
            }
        }

        $share->increment('download_count');

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    private function findValidShare(string $token): ProjectFileShare
    {
        $share = ProjectFileShare::query()
            ->withoutGlobalScopes()
            ->where('token', $token)
            ->with(['file' => fn ($q) => $q->withoutGlobalScopes(), 'company'])
            ->first();

        abort_if(
            ! $share
            || $share->isExpired()
            || ! $share->file
            || ! Storage::disk($share->file->disk)->exists($share->file->path),
            404,
        );

        return $share;
    }
}
