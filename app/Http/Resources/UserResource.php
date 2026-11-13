<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'display_name' => $this->display_name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'about' => $this->about,
            'avatar_url' => $this->avatar_url,
            'address' => [
                'street' => $this->street,
                'city' => $this->city,
                'province' => $this->province,
                'zipcode' => $this->zipcode,
                'complete' => $this->hasCompleteAddress(),
            ],
            'has_store' => $this->store()->exists(),
            'unread_notifications' => $this->unreadNotifications()->count(),
            'wishlist_count' => $this->wishlist()->count(),
        ];
    }
}
