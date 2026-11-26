<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\{User, Petugas, Kecamatan, MasterDesaKelurahan};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class RegisteredUserController extends Controller
{
    public function create()
    {
        // Desa/kelurahan dikelompokkan per kecamatan agar dropdown desa bisa difilter di sisi klien.
        $desaByKecamatan = MasterDesaKelurahan::orderBy('nama_wilayah')->get()
            ->groupBy('kecamatan_id')
            ->map(fn ($rows) => $rows->map(fn ($row) => [
                'id' => $row->id,
                'nama' => ($row->jenis_wilayah === 'kelurahan' ? 'Kelurahan ' : 'Desa ') . $row->nama_wilayah,
            ])->values())
            ->all();

        return view('auth.register', [
            'kecamatan' => Kecamatan::orderBy('nama_kecamatan')->get(),
            'desaByKecamatan' => $desaByKecamatan,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_lengkap' => ['required','string','max:150'],
            'username' => ['required','string','min:8','max:80','regex:/^(?=.*[0-9]).+$/', Rule::unique('users','username')],
            'email' => ['required','email:rfc','max:255','unique:users,email'],
            'kecamatan_asal_id' => ['nullable','exists:kecamatan,id'],
            'desa_kelurahan_asal_id' => [
                'nullable',
                Rule::exists('master_desa_kelurahan', 'id')->where(
                    fn ($query) => $query->where('kecamatan_id', (int) $request->input('kecamatan_asal_id'))
                ),
            ],
            'nomor_whatsapp' => ['required','regex:/^(\\+62|62|0)8[0-9]{7,13}$/'],
            'password' => ['required','confirmed',Password::min(8)->numbers()],
        ], [
            'username.min' => 'Username minimal 8 karakter.',
            'username.regex' => 'Username wajib mengandung minimal 1 angka.',
            'username.unique' => 'Username sudah digunakan. Silakan pilih username lain.',
            'desa_kelurahan_asal_id.exists' => 'Desa/kelurahan tidak sesuai dengan kecamatan yang dipilih.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.numbers' => 'Password wajib mengandung minimal 1 angka.',
        ]);

        $user = DB::transaction(function () use ($data) {
            $account = User::create([
                'name' => $data['nama_lengkap'], 'username' => $data['username'],
                'email' => $data['email'], 'password' => Hash::make($data['password']), 'role' => 'petugas', 'email_verified_at' => now(),
            ]);
            Petugas::create([
                'user_id' => $account->id, 'nama_lengkap' => $data['nama_lengkap'],
                'kecamatan_asal_id' => $data['kecamatan_asal_id'] ?? null,
                'desa_kelurahan_asal_id' => ! empty($data['kecamatan_asal_id']) ? ($data['desa_kelurahan_asal_id'] ?? null) : null,
                'nomor_whatsapp' => $data['nomor_whatsapp'],
            ]);
            return $account;
        });

        return redirect()->route('login')->with('status', 'Registrasi berhasil. Akun Anda sudah aktif dan dapat digunakan untuk login.');
    }
}
