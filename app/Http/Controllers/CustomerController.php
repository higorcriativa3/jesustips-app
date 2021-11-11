<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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
        ->select("select * from wprk_wc_customer_lookup where email='arthur241000@gmail.com'");

        $customer = collect($customerResponse);
        $customerId = $customer->pluck('customer_id');

        $orderResponse = DB::connection('mysqlWc')
        ->select("select * from wprk_wc_order_stats where customer_id=". $customerId[0] ."", array(1));
        $order = collect($orderResponse);
        $orderStatus = $order->pluck("status");
        $status = $orderStatus[0] == "wc-processing" ? 1 : 0;
        
        return $status;
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
    public function show($id)
    {
        //
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
}