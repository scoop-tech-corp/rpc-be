<?php

namespace App\Http\Controllers\Transaction\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Trait PaymentVerificationTrait
 *
 * Reusable logic untuk sistem verifikasi bukti pembayaran 2 langkah:
 *   Step 1 → Staff upload bukti (uploadProof)      → status: 'pending'
 *   Step 2 → Finance/Manager konfirmasi (confirm)  → status: 'verified'
 *            atau tolak (reject)                   → status: 'rejected'
 *
 * 4-Eyes principle: uploadedBy ≠ confirmedBy (wajib orang berbeda)
 * Hash check: SHA-256 file, cegah bukti yang sama dipakai di 2 transaksi
 */
trait PaymentVerificationTrait
{
    /**
     * Upload bukti pembayaran ke record payment.
     *
     * @param  \Illuminate\Database\Eloquent\Model $record  — Eloquent model dari tabel payment
     * @param  \Illuminate\Http\Request            $request
     * @param  string                              $storagePath  — subfolder di storage/app/public/
     * @param  string                              $dupCheckTable — nama tabel untuk cek hash duplikat
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleUploadProof($record, Request $request, string $storagePath, string $dupCheckTable)
    {
        if (!$record) {
            return responseInvalid(['Data pembayaran tidak ditemukan.']);
        }

        if ($record->isPayed == 1 || $record->isPayed == 2) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pembayaran ini sudah dikonfirmasi sebelumnya.',
            ], 400);
        }

        if ($record->verificationStatus === 'verified') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Bukti pembayaran sudah terverifikasi.',
            ], 400);
        }

        $file        = $request->file('proof');
        $fileContent = file_get_contents($file->getRealPath());
        $hash        = hash('sha256', $fileContent);

        // ── Cek duplikat hash ───────────────────────────────────────────────────
        $duplicate = \DB::table($dupCheckTable)
            ->where('proofHash', $hash)
            ->where('id', '!=', $record->id)
            ->where('isDeleted', 0)
            ->first();

        if ($duplicate) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Bukti pembayaran ini sudah pernah digunakan pada transaksi lain. Pastikan bukti transfer yang Anda upload adalah yang terbaru.',
            ], 422);
        }

        // ── Hapus file lama jika ada (re-upload) ───────────────────────────────
        if ($record->proofOfPayment) {
            Storage::disk('public')->delete($record->proofOfPayment);
        }

        $originalName = $file->getClientOriginalName();
        $randomName   = 'proof_' . $record->id . '_' . time() . '.' . $file->getClientOriginalExtension();

        if (!Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->makeDirectory($storagePath);
        }

        $filePath = $file->storeAs($storagePath, $randomName, 'public');

        $record->proofOfPayment     = $filePath;
        $record->originalName       = $originalName;
        $record->proofRandomName    = $randomName;
        $record->proofHash          = $hash;
        $record->uploadedBy         = $request->user()->id;
        $record->verificationStatus = 'pending';
        $record->verificationNote   = null;
        $record->verifiedAt         = null;
        $record->confirmedBy        = null;
        $record->updated_at         = now();
        $record->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Bukti pembayaran berhasil diunggah. Menunggu konfirmasi dari Finance atau Manager.',
            'data'    => [
                'id'                 => $record->id,
                'verificationStatus' => 'pending',
                'uploadedBy'         => $request->user()->id,
            ],
        ], 200);
    }

    /**
     * Konfirmasi bukti pembayaran (4-eyes: harus berbeda dari uploader).
     * Caller bertanggung jawab untuk update isPayed dan status transaksi.
     *
     * @param  \Illuminate\Database\Eloquent\Model $record
     * @param  \Illuminate\Http\Request            $request
     * @return array ['ok' => bool, 'response' => JsonResponse|null, 'record' => model]
     */
    protected function handleConfirmProof($record, Request $request): array
    {
        if (!$record) {
            return ['ok' => false, 'response' => responseInvalid(['Data pembayaran tidak ditemukan.'])];
        }

        if ($record->isPayed == 1 || $record->isPayed == 2) {
            return ['ok' => false, 'response' => response()->json([
                'status'  => 'error',
                'message' => 'Pembayaran ini sudah dikonfirmasi sebelumnya.',
            ], 400)];
        }

        if (!$record->proofOfPayment || $record->verificationStatus !== 'pending') {
            return ['ok' => false, 'response' => response()->json([
                'status'  => 'error',
                'message' => 'Bukti pembayaran belum diunggah atau status tidak valid. Minta staff untuk upload bukti terlebih dahulu.',
            ], 422)];
        }

        // ── 4-Eyes ─────────────────────────────────────────────────────────────
        if ($record->uploadedBy && (int) $record->uploadedBy === (int) $request->user()->id) {
            return ['ok' => false, 'response' => response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak dapat mengkonfirmasi pembayaran yang buktinya Anda upload sendiri.',
            ], 403)];
        }

        $record->confirmedBy        = $request->user()->id;
        $record->verificationStatus = 'verified';
        $record->verifiedAt         = now();

        return ['ok' => true, 'response' => null, 'record' => $record];
    }

    /**
     * Tolak bukti pembayaran (4-eyes: harus berbeda dari uploader).
     *
     * @param  \Illuminate\Database\Eloquent\Model $record
     * @param  \Illuminate\Http\Request            $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleRejectProof($record, Request $request)
    {
        if (!$record) {
            return responseInvalid(['Data pembayaran tidak ditemukan.']);
        }

        if ($record->isPayed == 1 || $record->isPayed == 2) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pembayaran sudah dikonfirmasi, tidak bisa ditolak.',
            ], 400);
        }

        if ($record->verificationStatus !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya pembayaran berstatus pending yang bisa ditolak.',
            ], 400);
        }

        if ($record->uploadedBy && (int) $record->uploadedBy === (int) $request->user()->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak dapat menolak bukti pembayaran yang Anda upload sendiri.',
            ], 403);
        }

        $record->verificationStatus = 'rejected';
        $record->verificationNote   = $request->note;
        $record->confirmedBy        = $request->user()->id;
        $record->verifiedAt         = now();
        $record->updated_at         = now();
        $record->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Bukti pembayaran ditolak. Staff dapat upload ulang bukti yang benar.',
        ], 200);
    }
}
