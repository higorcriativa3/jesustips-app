<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        // dd($status);

        if (! $token = auth()->attempt($credentials)) {
            try {
                $customerResponse = DB::connection('mysqlWc')
                ->select("select * from wprk_wc_customer_lookup where email='" . $credentials['email'] . "'");

                $customer = collect($customerResponse);
                $customerId = $customer->pluck('customer_id');

                $orderResponse = DB::connection('mysqlWc')
                ->select("select * from wprk_wc_order_stats where customer_id=". $customerId[0] ."", array(1));
                $order = collect($orderResponse);
                $orderStatus = $order->where("status", "wc-processing")->pluck("status");
                $status = $orderStatus[0] == "wc-processing" ? 1 : 0;

                if($status){
                    return $this->respondWithToken('token-from-woocommerce');
                }
            } catch (\Throwable $th) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}

