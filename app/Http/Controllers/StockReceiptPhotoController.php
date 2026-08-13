<?php

namespace App\Http\Controllers;

use App\Models\ItemStockMovement;
use App\Models\StockReceiptChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockReceiptPhotoController extends Controller
{
    public function active(
        Request $request,
        ItemStockMovement $stockReceipt
    ): BinaryFileResponse {
        $this->authorizeMovement(
            $request,
            $stockReceipt
        );

        return $this->imageResponse(
            $stockReceipt->photo_path,
            $stockReceipt->receipt_code
                ?: 'barang-masuk'
        );
    }

    public function proposed(
        Request $request,
        ItemStockMovement $stockReceipt
    ): BinaryFileResponse {
        $this->authorizeMovement(
            $request,
            $stockReceipt
        );

        $changeRequest =
            StockReceiptChangeRequest::query()
                ->where(
                    'item_stock_movement_id',
                    $stockReceipt->id
                )
                ->where(
                    'status',
                    StockReceiptChangeRequest::
                        STATUS_PENDING
                )
                ->latest('id')
                ->firstOrFail();

        $payload =
            is_array(
                $changeRequest
                    ->requested_payload
            )
                ? $changeRequest
                    ->requested_payload
                : [];

        abort_unless(
            ! empty(
                $payload[
                    'replace_photo'
                ]
            ),
            404,
            'Permintaan perubahan tidak menyertakan foto baru.'
        );

        return $this->imageResponse(
            $payload['photo_path']
                ?? null,
            (
                $stockReceipt
                    ->receipt_code
                ?: 'barang-masuk'
            ).
            '-usulan'
        );
    }

    private function authorizeMovement(
        Request $request,
        ItemStockMovement $movement
    ): void {
        $user =
            $request->user();

        abort_if(
            $user === null,
            401
        );

        abort_unless(
            in_array(
                (string)
                $user->role,
                [
                    'admin',
                    'toolman',
                    'kepala_bengkel',
                ],
                true
            ),
            403
        );

        if (
            (string)
            $user->role
            === 'admin'
        ) {
            return;
        }

        abort_unless(
            $user->workshop_id !== null
            && (int)
                $user->workshop_id
                === (int)
                    $movement
                        ->workshop_id,
            403,
            'Foto Barang Masuk hanya dapat dilihat oleh pengguna pada jurusan yang sama.'
        );
    }

    private function imageResponse(
        ?string $path,
        string $downloadName
    ): BinaryFileResponse {
        abort_if(
            $path === null
            || trim($path) === '',
            404,
            'Foto Barang Masuk belum tersedia.'
        );

        $disk =
            Storage::disk('public');

        abort_unless(
            $disk->exists($path),
            404,
            'File foto Barang Masuk tidak ditemukan.'
        );

        $absolutePath =
            $disk->path($path);

        $mimeType =
            $disk->mimeType($path)
            ?: 'application/octet-stream';

        abort_unless(
            str_starts_with(
                strtolower($mimeType),
                'image/'
            ),
            415,
            'File bukan gambar yang valid.'
        );

        $extension =
            pathinfo(
                $absolutePath,
                PATHINFO_EXTENSION
            );

        $safeName =
            preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '-',
                $downloadName
            )
            ?: 'barang-masuk';

        if ($extension !== '') {
            $safeName .=
                '.'.
                strtolower($extension);
        }

        return response()
            ->file(
                $absolutePath,
                [
                    'Content-Type' =>
                        $mimeType,

                    'Content-Disposition' =>
                        'inline; filename="'.
                        $safeName.
                        '"',

                    'Cache-Control' =>
                        'private, max-age=300, must-revalidate',

                    'X-Content-Type-Options' =>
                        'nosniff',
                ]
            );
    }
}
