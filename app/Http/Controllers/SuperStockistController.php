<?php

namespace App\Http\Controllers;

use App\Http\Resources\StockistResource;
use App\Http\Resources\SuperStockiestResource;
use App\Models\PlayDetails;
use App\Models\PlayMaster;
use App\Models\RechargeToUser;
use App\Models\StockistToTerminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SuperStockistController extends Controller
{

    public function get_all_super_stockist(){

        $data = User::whereUserTypeId(5)->get();

        return response()->json(['success'=>1, 'data' => SuperStockiestResource::collection($data)], 200);
//        return response()->json(['success'=>1, 'data' => $data], 200);
    }

    public function create_super_stockist(Request $request){
        $requestedData = (object)$request->json()->all();

        $user = new User();
        $user->user_name = $requestedData->userName;
        $user->email = $requestedData->pin;
        $user->password = md5($requestedData->pin);
        $user->user_type_id = 5;
        $user->commission = $requestedData->commission;
        $user->opening_balance = 0;
        $user->closing_balance = 0;
        $user->save();

        if ($user){
            $stockistToTerminal = new StockistToTerminal();
            $stockistToTerminal->super_stockist_id = $user->id;
            $stockistToTerminal->save();
        }

        return response()->json(['success'=>1, 'data' => $user], 200);
    }

    public function update_super_stockist(Request $request){
        $requestedData = (object)$request->json()->all();

        $user = User::find($requestedData->id);
        $user->user_name = $requestedData->userName;
        $user->save();

        return response()->json(['success'=>1, 'data' => $user], 200);
    }

    public function update_balance_to_super_stockist(Request $request){
        $requestedData = (object)$request->json()->all();
        $rules = array(
            'beneficiaryUid'=> ['required',
                function($attribute, $value, $fail){
                    $stockist=User::where('id', $value)->where('user_type_id','=',5)->first();
                    if(!$stockist){
                        return $fail($value.' is not a valid super stockist id');
                    }
                }],
        );
        $messages = array(
            'beneficiaryUid.required' => "Super Stockist required"
        );

        $validator = Validator::make($request->all(),$rules,$messages);
        if ($validator->fails()) {
            return response()->json(['success'=>0, 'data' => $messages], 500);
        }


        DB::beginTransaction();
        try{
            $requestedData = (object)$request->json()->all();
            $beneficiaryUid = $requestedData->beneficiaryUid;
            $amount = $requestedData->amount;
            $beneficiaryObj = User::find($beneficiaryUid);
            $beneficiaryObj->closing_balance = $beneficiaryObj->closing_balance + $amount;
            $beneficiaryObj->save();

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
        return response()->json(['success'=>1,'data'=> new StockistResource($beneficiaryObj)], 200,[],JSON_NUMERIC_CHECK);
    }

    public function barcode_wise_report_by_date(Request $request){
        $requestedData = (object)$request->json()->all();
        $start_date = $requestedData->startDate;
        $end_date = $requestedData->endDate;

        $data = PlayMaster::select('play_masters.id as play_master_id','games.game_name', DB::raw('substr(play_masters.barcode_number, 1, 8) as barcode_number')
            ,'draw_masters.visible_time as draw_time', 'games.game_name',
            'users.email as terminal_pin','play_masters.created_at as ticket_taken_time','stockist_to_terminals.super_stockist_id'
        )
            ->join('draw_masters','play_masters.draw_master_id','draw_masters.id')
            ->join('games','play_masters.game_id','games.id')
            ->join('users','users.id','play_masters.user_id')
            ->join('stockist_to_terminals','users.id','stockist_to_terminals.terminal_id')
            ->join('play_details','play_details.play_master_id','play_masters.id')
            ->where('play_masters.is_cancelled',0)
            ->where('stockist_to_terminals.super_stockist_id',$requestedData->superStockiestId)
            ->whereRaw('date(play_masters.created_at) >= ?', [$start_date])
            ->whereRaw('date(play_masters.created_at) <= ?', [$end_date])
            ->groupBy('play_masters.id','play_masters.barcode_number','draw_masters.visible_time','users.email','play_masters.created_at','games.game_name','stockist_to_terminals.super_stockist_id')
            ->orderBy('play_masters.created_at','desc')
            ->get();

        $cPanelReportController = new CPanelReportController();
        foreach($data as $x){
            $detail = (object)$x;
            $detail->total_quantity = $cPanelReportController->get_total_quantity_by_barcode($detail->play_master_id);
            $detail->prize_value = $cPanelReportController->get_prize_value_by_barcode($detail->play_master_id);
            $detail->amount = $cPanelReportController->get_total_amount_by_barcode($detail->play_master_id);
        }
        return response()->json(['success'=> 1, 'data' => $data], 200);
    }


    public function customer_sale_reports(Request $request){
        $requestedData = (object)$request->json()->all();
        $start_date = $requestedData->startDate;
        $end_date = $requestedData->endDate;


        $data = DB::select("select table1.play_master_id, table1.terminal_pin, table1.user_name, table1.user_id, table1.stockist_id ,table1.total, table1.commission, users.user_name as stokiest_name, super_stockist_id from
(select max(play_master_id) as play_master_id,terminal_pin,user_name,user_id,stockist_id,super_stockist_id,
        sum(total) as total,round(sum(commission),2) as commission from (
        select max(play_masters.id) as play_master_id,users.user_name,users.email as terminal_pin,
        round(sum(play_details.quantity * play_details.mrp)) as total,stockist_to_terminals.super_stockist_id,
        sum(play_details.quantity * play_details.mrp)* (max(play_details.commission)/100) as commission,
        play_masters.user_id, stockist_to_terminals.stockist_id
        FROM play_masters
        inner join play_details on play_details.play_master_id = play_masters.id
        inner join game_types ON game_types.id = play_details.game_type_id
        inner join users ON users.id = play_masters.user_id
        left join stockist_to_terminals on play_masters.user_id = stockist_to_terminals.terminal_id
        where play_masters.is_cancelled=0 and date(play_masters.created_at) >= ? and date(play_masters.created_at) <= ? and stockist_to_terminals.super_stockist_id = ?
        group by stockist_to_terminals.stockist_id,stockist_to_terminals.super_stockist_id, play_masters.user_id,users.user_name,play_details.game_type_id,users.email) as table1
        group by user_name,user_id,terminal_pin,stockist_id,super_stockist_id) as table1
        left join users on table1.stockist_id = users.id",[$start_date,$end_date,$requestedData->superStockiestId]);

//        $data = DB::select("select max(play_master_id) as play_master_id,terminal_pin,user_name,user_id,
//        sum(total) as total,round(sum(commission),2) as commission from (
//        select max(play_masters.id) as play_master_id,users.user_name,users.email as terminal_pin,
//        round(sum(play_details.quantity * play_details.mrp)) as total,
//        sum(play_details.quantity * play_details.mrp)* (max(play_details.commission)/100) as commission,
//        play_masters.user_id
//        FROM play_masters
//        inner join play_details on play_details.play_master_id = play_masters.id
//        inner join game_types ON game_types.id = play_details.game_type_id
//        inner join users ON users.id = play_masters.user_id
//        where play_masters.is_cancelled=0 and date(play_masters.created_at) >= ? and date(play_masters.created_at) <= ?
//        group by play_masters.user_id,users.user_name,play_details.game_type_id,users.email) as table1 group by user_name,user_id,terminal_pin",[$start_date,$end_date]);

        $cPanelReportController = new CPanelReportController();

        foreach($data as $x){
            $newPrize = 0;
            $tempntp = 0;
            $newData = PlayMaster::where('user_id',$x->user_id)->get();
            foreach($newData as $y) {
                $tempData = 0;
                $newPrize += $cPanelReportController->get_prize_value_by_barcode($y->id);
                $tempData = (PlayDetails::select(DB::raw("if(game_type_id = 1,(mrp*22)*quantity-(commission/100),mrp*quantity-(commission/100)) as total"))
                    ->where('play_master_id',$y->id)->distinct()->get())[0];
                $tempntp += $tempData->total;
            }
            $detail = (object)$x;
            $detail->prize_value = $newPrize;
            $detail->ntp = $tempntp;
        }
        return response()->json(['success'=> 1, 'data' => $data], 200);

    }

}
