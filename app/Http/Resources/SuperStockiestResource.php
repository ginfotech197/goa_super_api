<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SuperStockiestResource extends JsonResource
{

    public function toArray($request)
    {
        return [

            'userId' => $this->id,
            'userName' => $this->user_name,
            'pin' => $this->email,
            'userTypeId' => $this->user_type_id,
            'balance' => $this->closing_balance,

        ];
    }
}
