<?php

namespace App\Http\Controllers;

use App\Http\Resources\ManualResultResource;
use App\Models\DrawMaster;
use App\Models\ManualResult;
use App\Models\ResultMaster;
use App\Models\SingleNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManualResultController extends Controller
{

    public function index()
    {
        //
    }

    public function save_manual_result(Request $request)
    {
//
        $requestedData = (object)$request->json()->all();

        $drawMasterTemp = DrawMaster::whereGameId($requestedData->gameId)->whereId($requestedData->drawMasterId)->first();
        if ($drawMasterTemp->is_draw_over === 'yes'){

            $manualResult = new ManualResult();
            $manualResult->draw_master_id = $requestedData->drawMasterId;
            $manualResult->number_combination_id = $requestedData->numberCombinationId;
            $manualResult->game_id = $requestedData->gameId;
            $manualResult->game_date = Carbon::today();
            $manualResult->save();

            $resultMaster = new ResultMaster();
            $resultMaster->draw_master_id = $requestedData->drawMasterId;
            $resultMaster->number_combination_id = $requestedData->numberCombinationId;
            $resultMaster->game_id = $requestedData->gameId;
            $resultMaster->game_date = Carbon::today();
            $resultMaster->save();

            return response()->json(['success'=>1,'data'=> new ManualResultResource($manualResult)], 200,[],JSON_NUMERIC_CHECK);
        }else{
            $manualResult = ManualResult::whereGameId($requestedData->gameId)->whereGameDate(Carbon::today())->first();

            if($manualResult){
//                $manualResult = new ManualResult();
                $manualResult->draw_master_id = $requestedData->drawMasterId;
                $manualResult->number_combination_id = $requestedData->numberCombinationId;
                $manualResult->game_id = $requestedData->gameId;
                $manualResult->game_date = Carbon::today();
                $manualResult->update();
            }else{
                $manualResult = new ManualResult();
                $manualResult->draw_master_id = $requestedData->drawMasterId;
                $manualResult->number_combination_id = $requestedData->numberCombinationId;
                $manualResult->game_id = $requestedData->gameId;
                $manualResult->game_date = Carbon::today();
                $manualResult->save();
            }

            return response()->json(['success'=>1,'data'=> new ManualResultResource($manualResult)], 200,[],JSON_NUMERIC_CHECK);
        }


    }





    public function insert_old_result_by_date(Request $request)
    {
        $requestedData = (object)$request->json()->all();


            $manualResult = ManualResult::whereGameId($requestedData->gameId)->whereGameDate($requestedData->gameDate)->whereDrawMasterId($requestedData->drawMasterId)->first();

            if(empty($manualResult)){
                $manualResult = new ManualResult();
                $manualResult->draw_master_id = $requestedData->drawMasterId;
                $manualResult->number_combination_id = $requestedData->numberCombinationId;
                $manualResult->game_id = $requestedData->gameId;
                $manualResult->game_date = $requestedData->gameDate;
                $manualResult->save();

                $resultMaster = new ResultMaster();
                $resultMaster->draw_master_id = $requestedData->drawMasterId;
                $resultMaster->number_combination_id = $requestedData->numberCombinationId;
                $resultMaster->game_id = $requestedData->gameId;
//                $resultMaster->game_date = Carbon::today();
                $resultMaster->game_date = $requestedData->gameDate;
                $resultMaster->save();
            }

            return response()->json(['success'=>1,'data'=> new ManualResultResource($manualResult)], 200,[],JSON_NUMERIC_CHECK);

//
    }


    public function getInputLoadByGameId(Request $request){
        $requestedData = (object)$request->json()->all();
        $draw_id = $requestedData->drawId;
        $game_id = $requestedData->gameId;
        $retArray = [];
        $singleNumbers = SingleNumber::get();
        foreach ($singleNumbers as $x){
            $temp = DB::select("select max(play_details.quantity) as quanitty from play_masters
                    inner join play_details on play_details.play_master_id = play_masters.id
                    inner join number_combinations ON number_combinations.id = play_details.number_combination_id
                    where play_masters.draw_master_id = 1 and play_masters.game_id = 1 and play_details.game_type_id = 1 and number_combinations.single_number_id = ".$x->id."
                    group by play_details.number_combination_id
                    limit 1");
            if(!empty($temp)){
                $temp = $temp[0]->quanitty;
            }else{
                $temp = 0;
            }

           $y = array(
               'singleNumber'=>$x->id,
               'quantity'=> $temp,

               'numberCombinations'=> DB::select("select number_combinations.id as number_combination ,number_combinations.visible_triple_number ,ifnull(table1.quantity,0) as quantity from (select play_details.number_combination_id, sum(play_details.quantity) as quantity from play_masters
                    inner join play_details on play_details.play_master_id = play_masters.id
                    where play_masters.draw_master_id = ".$draw_id." and play_masters.game_id = ".$game_id." and play_details.game_type_id = 2
                    group by play_details.number_combination_id) as table1
                    right outer join number_combinations on table1.number_combination_id = number_combinations.id
                    where number_combinations.single_number_id = ".$x->id."
                    order by table1.quantity desc"),
           );

           array_push($retArray,(object)$y);

//            $data = DB::select("select number_combinations.id as number_combination , ifnull(table1.quantity,0) as quantity from (select play_details.number_combination_id, sum(play_details.quantity) as quantity from play_masters
//            inner join play_details on play_details.play_master_id = play_masters.id
//            where play_masters.draw_master_id = ".$draw_id." and play_masters.game_id = ".$game_id."
//            group by play_details.number_combination_id) as table1
//            right outer join number_combinations on table1.number_combination_id = number_combinations.id
//            where number_combinations.single_number_id = ".$x->id."
//            order by table1.quantity desc");
        }

        return response()->json(['success'=>1,'data'=> $retArray], 200,[],JSON_NUMERIC_CHECK);
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
     * @param  \App\Models\ManualResult  $manualResult
     * @return \Illuminate\Http\Response
     */
    public function show(ManualResult $manualResult)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ManualResult  $manualResult
     * @return \Illuminate\Http\Response
     */
    public function edit(ManualResult $manualResult)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ManualResult  $manualResult
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ManualResult $manualResult)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ManualResult  $manualResult
     * @return \Illuminate\Http\Response
     */
    public function destroy(ManualResult $manualResult)
    {
        //
    }
}
