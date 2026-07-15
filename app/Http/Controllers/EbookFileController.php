<?php

namespace App\Http\Controllers;

use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EbookFileController extends Controller
{
    /**
     * Helper to verify if the user has access to the ebook.
     */
    protected function checkAccess(\App\Models\User $user, Ebook $ebook): bool
    {
        // 1. Admin has full access
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // 2. Author/Publisher who created this ebook has access
        if ($ebook->user_id === $user->id) {
            return true;
        }

        // 3. Buyer who purchased this ebook has access
        if ($user->purchasedEbooks()->where('ebook_id', $ebook->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * View/Read the ebook inline (especially for PDFs).
     */
    public function view(Ebook $ebook)
    {
        $user = auth()->user();

        if (!$user || !$this->checkAccess($user, $ebook)) {
            abort(403, 'Anda tidak memiliki akses ke e-book ini.');
        }

        // Option 1: File is stored locally on the Shared VPS/Hosting
        if ($ebook->path_files) {
            if (!Storage::disk('local')->exists($ebook->path_files)) {
                abort(404, 'Berkas e-book tidak ditemukan di server.');
            }

            return Storage::disk('local')->response($ebook->path_files, $ebook->slug . '.pdf', [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $ebook->slug . '.pdf"'
            ]);
        }

        // Option 2: File is stored externally on a third party like Google Drive
        if ($ebook->url_files) {
            return redirect()->away($ebook->url_files);
        }

        abort(404, 'Sumber berkas e-book belum dikonfigurasi.');
    }

    /**
     * Securely download the ebook file.
     */
    public function download(Ebook $ebook)
    {
        $user = auth()->user();

        if (!$user || !$this->checkAccess($user, $ebook)) {
            abort(403, 'Anda tidak memiliki akses ke e-book ini.');
        }

        // Option 1: File is stored locally
        if ($ebook->path_files) {
            if (!Storage::disk('local')->exists($ebook->path_files)) {
                abort(404, 'Berkas e-book tidak ditemukan di server.');
            }

            return Storage::disk('local')->download($ebook->path_files, $ebook->slug . '.pdf');
        }

        // Option 2: File is stored externally
        if ($ebook->url_files) {
            return redirect()->away($ebook->url_files);
        }

        abort(404, 'Sumber berkas e-book belum dikonfigurasi.');
    }
}
