<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use DB;
use Auth;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;


class SavingsController extends Controller
{
    //
    public function get_savings(){
        return Auth::user();
    }

    public function save_money(Request $request){
        $current_user = Auth::user();
        
        //validate amount
        $data = $request->only('amount');
        $validator = Validator::make($data, [
            'amount' => 'required'
        ]);
        ////??////
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        $amount = $request->get('amount');
        if($amount < 100){
            return response()->json([
                'status' => false,
                'message' => 'Enter a valid amount'
            ]);
        }
       

        //Check if user has account 
        $user_id = Auth::user()->id;
        if(DB::table('tblsavings_account')->where('user_id', $user_id)->doesntExist()){
            // Create Savings Account 
            $last_id =  DB::table('tblsavings_account')->insertGetId([
                'user_id'=> $user_id,
                'entry_date' => date('Y-m-d'),
                'created_at' => date('y-m-d h:i:s')
            ]);
            //Update account number
            $account_no = '#0000-'.$last_id;
            DB::table('tblsavings_account')->where('id',$last_id)->update([
                'account_no' => $account_no,
                'updated_at' => date('y-m-d h:i:s')
            ]);
        }
        //Fetch Savings_ID
        $savings_id = DB::table('tblsavings_account')->where('user_id',$user_id)->value('id');

        // Insert transactions into savings_transactions_table
        DB::table('tblsavings_transaction')->insert([
            'savings_id'=> $savings_id,
            'user_id' => $user_id,
            'entry_date' => date('Y-m-d'),
            'created_at' => date('y-m-d h:i:s'),
            'amount' => $amount,
            'category'=> 'credit'
        ]);

        //If account exists, update balance table
            if(DB::table('tblsavings_balance')->where([
                ['user_id', "=", $user_id],
                ['savings_id', "=", $savings_id]
            ])->exists()){
                //Fetch balance
                $previous_amount = DB::table('tblsavings_balance')->where('savings_id', $savings_id)->value('balance');
                $new_balance = $previous_amount + $amount;
                //Update
                DB::table('tblsavings_balance')->where('savings_id', $savings_id)->update([
                    'balance' => $new_balance
                ]);
             }else{
                    DB::table('tblsavings_balance')->where(['savings_id', '=', $savings_id], ['user_id', '=', $user_id])->insert([
                        'savings_id' => $savings_id,
                        'user_id' => $user_id,
                        'balance' => $amount
                    ]);
             }

             return response()->json([
                'status' => 'true',
                'message' => 'N'.$amount.' added succesfully'
             ]);
        
    }

    public function savings_balance(){
        $user_id = Auth::user()->id;
        $savings_balance = DB::table('tblsavings_balance')->where('user_id', $user_id)->value('balance');
        
        if($savings_balance == NULL){
            $savings_balance = 0;
        }

        return response()->json([
            'status' => 'true',
            'message' => 'Success',
            'data' => $savings_balance
        ],200);
    }

    public function savings_transactions(Request $request){
       
            $first_day= date('Y-m-01');
            $last_day= date('Y-m-t');

            $from_date = $request->get('from_date');
            $to_date = $request->get('to_date');

            if($from_date == "" && $to_date == "" ){
                $from_date1 =  $first_day; 
                $to_date1 = $last_day;
            }else{
                $from_date1= $from_date;
                $to_date1 = $to_date;
            }
                $savings_transaction = DB::table('tblsavings_transaction')->where('category','=','credit')->whereBetween('entry_date',[$from_date1, $to_date1])->get();

            return response()->json([
                'status' => 'true',
                'message' => 'Success',
                'data' => $savings_transaction
            ],200);
    }

    public function dashboard(){
        //SAVINGS BALANCE
        $user_id = Auth::user()->id;
        $savings_balances = DB::table('tblsavings_balance')->where('user_id', $user_id)->value('balance');
        
        if($savings_balances == NULL){
            $savings_balances = 0;
        }

        //Savings Transactions
        $savings_transactions = DB::table('tblsavings_transaction')->orderBy('created_at', 'desc')->take(10)->get();


        return response()->json([
            'status' => 'true',
            'message' => 'Success',
            'data' => [
                'savings_balance' => $savings_balances,
                'savings_transactions' => $savings_transactions]

        ],200);
    }

    //WITHDRAWAL
    public function withdraw(Request $request){
        $user_id = Auth::user()->id;
        $savings_balance = DB::table('tblsavings_balance')->where('user_id', $user_id)->value('balance');
    //Amount Validator
    $data = $request->only('amount');
    $validator = Validator::make($data, [
        'amount' => 'required'
    ]);
    ////??////
    if ($validator->fails()) {
        return response()->json(['error' => $validator->messages()], 200);
    }

    $amount = $request->get('amount');
    if($amount <= 100 ){
        return response()->json([
            'status' => false,
            'message' => 'Enter a valid amount'
        ]);
    }elseif($amount > $savings_balance){
        return response()->json([
            'status' => false,
            'message' => 'Insufficient Funds!'
        ]);
    }

        $savings_id = DB::table('tblsavings_account')->where('user_id',$user_id)->value('id');

        // Insert transactions into savings_transactions_table
        DB::table('tblsavings_transaction')->insert([
            'savings_id'=> $savings_id,
            'user_id' => $user_id,
            'entry_date' => date('Y-m-d'),
            'created_at' => date('y-m-d h:i:s'),
            'amount' => $amount,
            'category'=> 'debit'
        ]);
        //If account exists, update balance table
        if(DB::table('tblsavings_balance')->where([
            ['user_id', "=", $user_id],
            ['savings_id', "=", $savings_id]
        ])->exists()){
            //Fetch balance
            $previous_amount = DB::table('tblsavings_balance')->where('savings_id', $savings_id)->value('balance');
            $new_balance = $previous_amount - $amount;

            

            //Update
            DB::table('tblsavings_balance')->where('savings_id', $savings_id)->update([
                'balance' => $new_balance
            ]);
         }else{
                DB::table('tblsavings_balance')->where(['savings_id', '=', $savings_id], ['user_id', '=', $user_id])->insert([
                    'savings_id' => $savings_id,
                    'user_id' => $user_id,
                    'balance' => $amount
                ]);
         }



        return response()->json([
            'status' => 'true',
            'message' => 'N'.$amount.' Withdrawn Successfully!!!' 
        ]);

    }

    //GET ALL TRANSACTIONS

    public function all_transactions(Request $request){

        $first_day= date('01-m-Y');
            $last_day= date('t-m-Y');

            $from_date = $request->get('from_date');
            $to_date = $request->get('to_date');

            if($from_date == "" && $to_date == "" ){
                $from_date1 =  $first_day; 
                $to_date1 = $last_day;
            }else{
                $from_date1= $from_date;
                $to_date1 = $to_date;
            }
                $savings_transaction = DB::table('tblsavings_transaction')->whereBetween('entry_date',[$from_date1, $to_date1])->paginate(10);

            return response()->json([
                'status' => 'true',
                'message' => 'Success',
                'data' => $savings_transaction
            ],200);
    }
}
