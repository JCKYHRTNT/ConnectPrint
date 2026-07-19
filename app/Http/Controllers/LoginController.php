<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    // SHOW LOGIN FORM
    public function show()
    {
        return view('login');
    }

    // HANDLE LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Invalid email or password.');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->put([
            'user_id' => $user->id,
            'name'    => $user->name,
            'role'    => $user->role,
        ]);

        if ($user->role === 'admin') {
            return redirect()
                ->route('admin.user', ['username' => $user->slug])
                ->with('success', 'Logged in as admin.');
        }

        return redirect()
            ->route('home.user')
            ->with('success', 'Logged in.');
    }

    // SHOW REGISTER FORM
    public function showRegister()
    {
        return view('register');
    }

    // HANDLE REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:4', 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'user',
            'profpic'  => null,
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->put([
            'user_id' => $user->id,
            'name'    => $user->name,
            'role'    => $user->role,
        ]);

        return redirect()
            ->route('home.user')
            ->with('success', 'Account created and logged in.');
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Logged out.');
    }
}
