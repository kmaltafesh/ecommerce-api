<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    //
     public function register(Request $request)
    {
        //Validate the request
        $data = $request->validate([
            'name' => 'required|string|max:250',
            'email' => 'required|string|email|max:250|unique:users',
            'password' => 'required|string|min:8|confirmed'
        ]);

        //Hash the password
        $data['password'] = Hash::make($data['password']);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'type'=>$data['customer'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'messsage' => 'User registered successful',
            'user' => $user,
            'token' => $token
        ], 201);
    }
    public function login(Request $request)
    {

        $data = $request->validate([
            'email' => 'required|string|email|max:250',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();
        if (!Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User Logged in successfully',
            'user' => $user,
            'token' => $token
        ], 200);
    }
    public function logout(Request $request)
    {

        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => "User Logged out successfully"], 200);
    }
    //get user(profile)
    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()], 200);
    }

    public function getAccessToken(Request $request)
    {
        return response()->json(['token'=>$request->user()->currentAccessToken()],200);
    }
}
