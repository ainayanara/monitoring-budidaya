<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username'      => 'required|unique:users',
            'nama_depan'    => 'required',
            'nama_belakang' => 'required',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:6',
            'peran'         => 'required|in:siswa,mentor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'username'      => $request->username,
            'nama_depan'    => $request->nama_depan,
            'nama_belakang' => $request->nama_belakang,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'peran'         => $request->peran,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'       => 'success',
            'message'      => 'Registrasi berhasil',
            'token'        => $token,
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'role'         => $user->peran,
            'user'         => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email atau password salah',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'       => 'success',
            'message'      => 'Login berhasil',
            'token'        => $token,
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'role'         => $user->peran,
            'user'         => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * GET /api/profile
     * Ambil data profil user yang sedang login.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'            => $user->id,
                'username'      => $user->username,
                'nama_depan'    => $user->nama_depan,
                'nama_belakang' => $user->nama_belakang,
                'email'         => $user->email,
                'peran'         => $user->peran,
            ],
        ]);
    }

    /**
     * PUT /api/profile
     * Update nama depan, nama belakang, dan username.
     * Email dan peran tidak bisa diubah oleh user sendiri.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'nama_depan'    => 'required|string|max:100',
            'nama_belakang' => 'required|string|max:100',
            'username'      => 'required|string|max:50|unique:users,username,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user->update([
            'nama_depan'    => $request->nama_depan,
            'nama_belakang' => $request->nama_belakang,
            'username'      => $request->username,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data'    => [
                'id'            => $user->id,
                'username'      => $user->username,
                'nama_depan'    => $user->nama_depan,
                'nama_belakang' => $user->nama_belakang,
                'email'         => $user->email,
                'peran'         => $user->peran,
            ],
        ]);
    }

    /**
     * POST /api/profile/password
     * Ganti password — wajib verifikasi password lama dulu.
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password_lama'         => 'required',
            'password'              => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Verifikasi password lama
        if (!Hash::check($request->password_lama, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Password lama tidak sesuai',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Password berhasil diubah',
        ]);
    }
}