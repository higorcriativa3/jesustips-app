<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Mail\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customerResponse = DB::connection('mysqlWc')
        ->select("select * from wprk_wc_customer_lookup where email='junior_085@live.com'");

        return $customerResponse;

        $customer = collect($customerResponse);
        $customerId = $customer->pluck('customer_id');

        $orderResponse = DB::connection('mysqlWc')
        ->select("select * from wprk_wc_order_stats where customer_id=". $customerId[0] ."", array(1));
        $order = collect($orderResponse);
        $orderStatus = $order->where("status", "wc-processing")->pluck("status");
        $status = $orderStatus[0] == "wc-processing" ? 1 : 0;
        
        return $orderResponse;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $customerId = $request->customerId;

        if($request->token == env("FRONT_TOKEN")){
            $customerResponse = DB::connection('mysqlWc')
            ->select("select * from wprk_wc_customer_lookup where customer_id='". $customerId ."'");

            return $customerResponse;
        } else {
            return response(["Message" => "Unauthorized"], 401);
        }
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    /**
     * Update password.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function passwordResetProcess(Request $request){
        try{
          // Validate token
          $dbToken = DB::table('password_resets')
            ->where('email', $request->email)
            ->value('token');
  
          // dd($dbToken);
  
          if($dbToken != $request->passwordToken || $request->passwordToken == null || $request->passwordToken == "" ) {
            return response()->json(['message' => 'Token invalido'], 400);
          }
          // find email
          $userData = User::whereEmail($request->email)->firstOrFail();
          // update password
          $userData->update([
            'password'=> Hash::make($request->password)
          ]);
        } catch(\Exception $e) {
          return response(['Message'=>$e->getMessage()], 500);
        }
        
    }

    public function sendPasswordResetEmail(Request $request){
        // If email does not exist
        if(!$this->validEmail($request->email)) {
            return response()->json([
                'message' => 'Email does not exist.'
            ], Response::HTTP_NOT_FOUND);
        } else {
            // If email exists
            $this->sendMail($request->email);
            return response()->json([
                'message' => 'Check your inbox, we have sent a link to reset email.'
            ], Response::HTTP_OK);            
        }
    }


    public function sendMail($email){
        $token = $this->generateToken($email);
        Mail::to($email)->send(new PasswordReset($token));
    }

    public function validEmail($email) {
       return !!User::where('email', $email)->first();
    }

    public function generateToken($email){
      $token = str_replace('/','',Hash::make(Str::random(40)));
      $this->storeToken($token, $email);
      return $token;
    }

    public function storeToken($token, $email){
        DB::table('password_resets')
        ->upsert([
            'email' => $email,
            'token'=> $token,          
        ], ['email'], ['token']);
    }
}
