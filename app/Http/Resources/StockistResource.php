<?php

namespace App\Http\Resources;

use App\Models\StockistToTerminal;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class StockistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [

            'userId' => $this->id,
            'userName' => $this->user_name,
            'pin' => $this->email,
//            'userTypeId' => ($this->user_type)->id,
            // 'superStockiestId' => $this->super_stockist_id,
            'superStockiest' => new SuperStockiestResource(User::find((StockistToTerminal::whereStockistId($this->id)->first())->super_stockist_id)),
            // 'superStockistName' =>User::find($this->super_stockist_id),
            // 'superStockistName' =>User::find($this->super_stockist_id),
            'userTypeId' => $this->user_type_id,
            'balance' => $this->closing_balance,
            'commission' => $this->commission,

        ];
    }
}
