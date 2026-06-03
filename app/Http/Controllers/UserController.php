<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserController extends Controller
{
    private function adminOnly()
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403, 'Hanya admin yang dapat mengakses halaman ini.');
        }
    }

    public function index(Request $request)
    {
        $this->adminOnly();

        $search = $request->get('search', '');

        $users = User::when($search, fn($q) => $q->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%"))
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'role'       => $u->role,
                'created_at' => $u->created_at->format('d M Y'),
                'is_self'    => $u->id === auth()->id(),
                'initial'    => strtoupper(substr($u->name, 0, 1)),
            ]);

        $stats = [
            'total'   => User::count(),
            'admin'   => User::where('role', 'admin')->count(),
            'petugas' => User::where('role', 'petugas')->count(),
        ];

        return Inertia::render('Users/Index', [
            'users'  => $users,
            'stats'  => $stats,
            'search' => $search,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = [
            'name'  => $request->name,
            'email' => $request->email,
            'role'  => $request->role,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->adminOnly();

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        // Cegah hapus admin terakhir
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya admin.');
        }

        $user->delete();
        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    public function resetPassword(User $user)
    {
        $this->adminOnly();
        $user->update(['password' => Hash::make('password')]);
        return back()->with('success', "Password {$user->name} berhasil direset ke 'password'.");
    }
}
