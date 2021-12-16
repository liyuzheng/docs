<?php


namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller;

class BaseController extends Controller
{
    public function getAuthAdminId()
    {
        return request()->user()->admin_id;
    }

    public function getAuthUser()
    {
        return request()->user();
    }
}
