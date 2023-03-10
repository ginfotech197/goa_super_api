<?php

namespace App\Http\Controllers;
use App\Models\UserType;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\RechargeToUser;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use App\Models\CustomVoucher;

use App\Http\Controllers\Controller;
use App\Http\Resources\TerminalResource;
use App\Models\StockistToTerminal;
use Illuminate\Http\Request;
/////// for log
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class TerminalController extends Controller
{
    public function get_all_terminals(){
        $terminals = UserType::find(4)->users;
        return TerminalResource::collection($terminals);
    }

    public function reset_user_password(Request $request){
        $requestedData = (object)$request->json()->all();
        $user = User::find($requestedData->userId);
        if(Hash::check($requestedData->password, $user->password)){
            $user->password = md5($requestedData->newPassword);
            $user->save();
            return response()->json(['success'=>1], 200,[],JSON_NUMERIC_CHECK);
        }
        return response()->json(['success'=>0], 200,[],JSON_NUMERIC_CHECK);
    }

    // public function get_stockist_by_terminal_id(){
    //     $trminals = User::find(StockistToTerminal::whereTerminalId(14)->first()->stockist_id);
    //     return response()->json(['success'=>0, 'data' => $trminals], 500);
    // }



    public function create_terminal(Request $request){
        $requestedData = (object)$request->json()->all();

        DB::beginTransaction();
        try{
            $customVoucher=CustomVoucher::where('voucher_name','=',"terminal")->where('accounting_year',"=",2021)->first();
            if($customVoucher) {
                //already exist
                $customVoucher->last_counter = $customVoucher->last_counter + 1;
                $customVoucher->save();
            }else{
                //fresh entry
                $customVoucher= new CustomVoucher();
                $customVoucher->voucher_name="terminal";
                $customVoucher->accounting_year= 2021;
                $customVoucher->last_counter=3000;
                $customVoucher->delimiter='-';
                $customVoucher->prefix='T';
                $customVoucher->save();
            }
            //adding Zeros before number
            $counter = $customVoucher->last_counter;
            //creating stockist user_id
            $user_id = $counter;

            $user = new User();
            $user->user_name = $requestedData->terminalName;
            $user->email = $user_id;
            $user->password = md5($user_id);
            $user->commission = $requestedData->commission;
            $user->user_type_id = 4;
            $user->opening_balance = 0;
            $user->closing_balance = 0;

            $user->save();

            if($user){
                $stockistToTerminal = StockistToTerminal::whereStockistId($requestedData->stockistId)
                    ->whereSuperStockistId($requestedData->superStockistId)
                    ->whereTerminalId(null)
                    ->first();
                if($stockistToTerminal) {
                    $stockistToTerminal->terminal_id = $user->id;
                    $stockistToTerminal->save();
                }else{
                    $stockistToTerminal= new StockistToTerminal();
                    $stockistToTerminal->super_stockist_id = $requestedData->superStockistId;
                    $stockistToTerminal->stockist_id = $requestedData->stockistId;
                    $stockistToTerminal->terminal_id = $user->id;
                    $stockistToTerminal->save();
                }
            }



            DB::commit();
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['success'=>0, 'data' => null, 'error'=>$e->getMessage()], 500);
        }

        return response()->json(['success'=>1,'data'=> new TerminalResource($user)], 200,[],JSON_NUMERIC_CHECK);
    }


    public function update_terminal(Request $request){

        $requestedData = (object)$request->json()->all();

        $terminalId = $requestedData->terminalId;
        $terminalName = $requestedData->terminalName;
        $stockist_id = $requestedData->stockistId;

        $terminal = User::findOrFail($terminalId);
        $terminal->user_name = $terminalName;
        $terminal->commission = $requestedData->commission;
        $terminal->save();

        $stockistToTerminal = StockistToTerminal::where('terminal_id',$terminalId)->first();
        if(!empty($stockistToTerminal)){
            $stockistToTerminal->stockist_id = $stockist_id;
            $stockistToTerminal->save();
        }else{
            $stockistToTerminal = new StockistToTerminal();
            $stockistToTerminal->terminal_id = $terminalId;
            $stockistToTerminal->stockist_id = $stockist_id;
            $stockistToTerminal->save();
        }

        return response()->json(['success'=>1,'data'=> new TerminalResource($terminal), 'test1'=> $stockistToTerminal], 200,[],JSON_NUMERIC_CHECK);
        // return response()->json(['data'=> $stockistToTerminal]);
    }

    public function terminal_by_super_stockist($id){
        $data = StockistToTerminal::select(DB::raw("stockist_to_terminals.super_stockist_id,stockist_to_terminals.stockist_id ,users.id, users.user_name,users.email, users.user_type_id,users.closing_balance, users.commission, users.user_type_id"))
            ->join('users','users.id','stockist_to_terminals.terminal_id')
            ->where('stockist_to_terminals.super_stockist_id',$id)
            ->get();
        return response()->json(['success'=> 1, 'data' => TerminalResource::collection($data)], 200);
    }



    public function update_balance_to_terminal(Request $request){
        $requestedData = (object)$request->json()->all();

    // Validation for terminal
       $rules = array(
           'beneficiaryUid'=> ['required',
               function($attribute, $value, $fail){
                   $terminal=User::where('id', $value)->where('user_type_id','=',4)->first();
                   if(!$terminal){
                       return $fail($value.' is not a valid terminal id');
                   }
               }],
       );
       $messages = array(
           'beneficiaryUid.required' => "Terminal required"
       );

       $validator = Validator::make($request->all(),$rules,$messages);
       if ($validator->fails()) {
        return response()->json(['success'=>0, 'data' => $messages], 500);
    }

        DB::beginTransaction();
        try{

            $beneficiaryUid = $requestedData->beneficiaryUid;
            $amount = $requestedData->amount;
            $stockistId = $requestedData->stockistId;
            $beneficiaryObj = User::find($beneficiaryUid);
            $beneficiaryObj->closing_balance = $beneficiaryObj->closing_balance + $amount;
            $beneficiaryObj->save();

            $stockist = User::findOrFail($stockistId);
            $stockist->closing_balance = $stockist->closing_balance - $amount;
            $stockist->save();

            $rechargeToUser = new RechargeToUser();
            $rechargeToUser->beneficiary_uid = $requestedData->beneficiaryUid;
            $rechargeToUser->recharge_done_by_uid = $requestedData->rechargeDoneByUid;
            $rechargeToUser->amount = $requestedData->amount;
            $rechargeToUser->save();
            DB::commit();

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['success'=>0, 'data' => null, 'error'=>$e->getMessage()], 500);
        }
        return response()->json(['success'=>1,'data'=> new TerminalResource($beneficiaryObj)], 200,[],JSON_NUMERIC_CHECK);

    }

    public function reset_terminal_password(Request $request){
        $requestedData = (object)$request->json()->all();
        $terminalId = $requestedData->terminalId;
        $terminalPassword = $requestedData->terminalNewPassword;
        $terminal = User::find($terminalId);
        $terminal->password = md5($terminalPassword);
        $terminal->save();
        return response()->json(['success'=>1,'data'=>$terminal], 200,[],JSON_NUMERIC_CHECK);
    }

}
