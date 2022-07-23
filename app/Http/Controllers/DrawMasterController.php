<?php

namespace App\Http\Controllers;

use App\Http\Resources\DrawMasterResource;
use App\Models\DrawMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class DrawMasterController extends Controller
{

    public function index()
    {
        $result = DrawMaster::get();
        return response()->json(['success'=>1,'data'=>DrawMasterResource::collection($result)], 200,[],JSON_NUMERIC_CHECK);
    }


    public function get_draw_time_by_game_id($id)
    {
        $result = DrawMaster::whereGameId($id)->get();
        return response()->json(['success'=>1,'data'=>DrawMasterResource::collection($result)], 200,[],JSON_NUMERIC_CHECK);
    }

    public function get_incomplete_games_by_date($id){
        $test = Carbon::today();
//        $result = DrawMaster::whereDoesnthave('result_masters', function($q) use ($test) {
//            $q->where('game_date', '=', $test);
//        })
//            ->whereDoesnthave('manual_results', function($q) use ($test) {
//            $q->where(DB::raw('date(created_at)'), '=', $test);
//        })
        $result = DrawMaster::
            whereGameId($id)
            ->get();
        return response()->json(['success'=>1,'data'=>DrawMasterResource::collection($result)], 200,[],JSON_NUMERIC_CHECK);
    }


    public function get_draws_by_game_id_and_date(Request $request){
        $requestedData = (object)$request->json()->all();


        $gameDate = $requestedData->gameDate;
        $gameId= $requestedData->gameId;

        $data = DB::select("select * from draw_masters
        where id not in (select draw_master_id from result_masters where game_date='$gameDate' and game_id=$gameId)
        and game_id=$gameId");
        
        return response()->json(['success'=>1,'data'=>DrawMasterResource::collection($data)], 200,[],JSON_NUMERIC_CHECK);

    }

    public function getActiveDraw()
    {
        $result = DrawMaster::where('active',1)->first();
        if(!empty($result)){
            return response()->json(['success'=>1,'data'=> new DrawMasterResource($result)], 200,[],JSON_NUMERIC_CHECK);
        }else{
            return response()->json(['success'=>1,'data'=> null], 200,[],JSON_NUMERIC_CHECK);
        }
    }

    public function getGameActiveDraw($id)
    {
        $result = DrawMaster::where('active',1)->whereGameId($id)->first();
        if(!empty($result)){
            return response()->json(['success'=>1,'data'=> new DrawMasterResource($result)], 200,[],JSON_NUMERIC_CHECK);
        }else{
            return response()->json(['success'=>1,'data'=> null], 200,[],JSON_NUMERIC_CHECK);
        }
    }

    public function setActiveDraw(){

    }


}
