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

        // Store user info into session
        session([
            'user_id' => $user->id,
            'name'    => $user->name,
            'role'    => $user->role,
        ]);

        // Use accessor on User model
        $slug = $user->slug;

        if ($user->role === 'admin') {
            return redirect()
                ->route('admin.user', ['username' => $slug])
                ->with('success', 'Logged in as admin.');
        }

        return redirect()
            ->route('home.user', ['username' => $slug])
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

        session([
            'user_id' => $user->id,
            'name'    => $user->name,
            'role'    => $user->role,
        ]);

        $slug = $user->slug;

        return redirect()
            ->route('home.user', ['username' => $slug])
            ->with('success', 'Account created and logged in.');
    }

    // LOGOUT
    public function logout()
    {
        session()->flush();

        return redirect('/')->with('success', 'Logged out.');
    }
}