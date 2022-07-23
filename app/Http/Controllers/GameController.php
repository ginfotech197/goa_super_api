<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function getGame()
    {
        $game= Game::get();

        return response()->json(['success'=>1,'data'=> $game], 200,[],JSON_NUMERIC_CHECK);
    }

    public function update_auto_generate($id)
    {
        $game= Game::find($id);
        $game->auto_generate= $game->auto_generate=='yes'?'no':'yes';
        $game->update();
        return response()->json(['success'=>1,'data'=> $game], 200,[],JSON_NUMERIC_CHECK);

    }

    public function activate_game($id)
    {
        $game= Game::find($id);
        $game->active= $game->active=='yes'?'no':'yes';
        $game->update();
        return response()->json(['success'=>1,'data'=> $game], 200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function show(Game $game)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function edit(Game $game)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Game $game)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function destroy(Game $game)
    {
        //
    }
}
