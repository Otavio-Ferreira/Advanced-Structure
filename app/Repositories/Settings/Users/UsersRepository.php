<?php

namespace App\Repositories\Settings\Users;

use App\Http\Requests\Users\StoreRequest;
use App\Models\User;

interface UsersRepository{
    public function set(StoreRequest $request) : User;

    public function delete($id) : void;
}