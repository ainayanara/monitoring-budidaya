<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    KelompokController,
    KomoditasController,
    SopController,
    LahanController,
    KalenderController,
    AktivitasController,
    ProposalController,
    RabController,
    DiskusiController,
    DashboardController,
    UserController,
};

// ─── PUBLIC ───────────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ─── PRIVATE (Butuh Bearer Token) ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    
    // ── Profil user ────────────────────────────────────────────────────────────
    Route::get('/profile',          [AuthController::class, 'show']);
    Route::put('/profile',          [AuthController::class, 'update']);
    Route::post('/profile/password',[AuthController::class, 'updatePassword']);
 
    // ── Dashboard (sesuai role) ────────────────────────────────────────────────
    Route::get('/dashboard/siswa',  [DashboardController::class, 'siswa']);
    Route::get('/dashboard/mentor', [DashboardController::class, 'mentor']);

    // ── Master Data ────────────────────────────────────────────────────────────
    Route::get('/komoditas',          [KomoditasController::class, 'index']);
    Route::get('/sop/{id_komoditas}', [SopController::class, 'showByKomoditas']);
    Route::post('/sop/progress',      [SopController::class, 'toggleProgress']);

    // ── User helper (BARU — untuk dropdown mentor & anggota di form kelompok) ──
    Route::get('/users/mentor',          [UserController::class, 'listMentor']);
    Route::get('/users/siswa-tersedia',  [UserController::class, 'listSiswaTersedia']);

    // ── Fitur Utama (CRUD) ─────────────────────────────────────────────────────
    // PENTING: route statis/khusus (buat, tersedia) WAJIB didaftarkan SEBELUM
    // apiResource('kelompok', ...), supaya tidak tertangkap oleh pola
    // GET/POST /kelompok/{kelompok} milik apiResource.
    Route::post('/kelompok/buat',        [KelompokController::class, 'storeSimple']);
    Route::get('/kelompok/tersedia',     [KelompokController::class, 'tersedia']);
    Route::post('/kelompok/{id}/gabung', [KelompokController::class, 'gabung']);
    Route::get('/kelompok/{id}/progress', [KelompokController::class, 'progress']);

    Route::apiResource('kelompok',  KelompokController::class);

    Route::apiResource('lahan',     LahanController::class);
    Route::apiResource('kalender',  KalenderController::class);
    Route::apiResource('proposal',  ProposalController::class);
    Route::apiResource('rab',       RabController::class);
    Route::apiResource('diskusi',   DiskusiController::class);

    // Aktivitas — form-data harus di atas apiResource agar tidak bentrok dengan {id}
    Route::get('/aktivitas/form-data', [AktivitasController::class, 'formData'])
        ->middleware('can:is-siswa');
    Route::post('/aktivitas', [AktivitasController::class, 'store'])
        ->middleware('can:is-siswa');
    Route::put('/aktivitas/{aktivita}', [AktivitasController::class, 'update'])
        ->middleware('can:is-siswa');
    Route::delete('/aktivitas/{aktivita}', [AktivitasController::class, 'destroy'])
        ->middleware('can:is-siswa');
    Route::apiResource('aktivitas', AktivitasController::class)->except(['store', 'update', 'destroy']);

    // ── Diskusi reply ───────────────────────────────────────────────────────────
    Route::post('/diskusi/{id}/reply', [DiskusiController::class, 'reply']);

    // ── Aksi Siswa ──────────────────────────────────────────────────────────────
    Route::put('/aktivitas/{id}/update', [AktivitasController::class, 'updateProgress'])
        ->middleware('can:is-siswa');
    Route::post('/proposal/{id}/submit', [ProposalController::class, 'submit'])
        ->middleware('can:is-siswa');

    // ── Aksi Mentor ─────────────────────────────────────────────────────────────
    Route::post('/proposal/{id}/review', [ProposalController::class, 'review'])
        ->middleware('can:is-mentor');
    Route::post('/proposal/{id}/review-rab', [ProposalController::class, 'reviewRab'])
        ->middleware('can:is-mentor');
    Route::put('/aktivitas/{id}/verifikasi', [AktivitasController::class, 'verifikasi'])
        ->middleware('can:is-mentor');
});