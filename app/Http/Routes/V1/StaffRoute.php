<?php
namespace App\Http\Routes\V1;

use Illuminate\Contracts\Routing\Registrar;

class StaffRoute
{
    public function map(Registrar $router)
    {
        $router->group([
            'prefix' => 'staff',
            'middleware' => 'staff'
        ], function ($router) {
            // Ticket
            $router->get ('/ticket/fetch', 'V1\\Staff\\TicketController@fetch');
            $router->post('/ticket/reply', 'V1\\Staff\\TicketController@reply');
            $router->post('/ticket/close', 'V1\\Staff\\TicketController@close');
            // User
            $router->post('/user/update', 'V1\\Staff\\UserController@update');
            $router->get ('/user/getUserInfoById', 'V1\\Staff\\UserController@getUserInfoById');
            $router->post('/user/sendMail', 'V1\\Staff\\UserController@sendMail');
            $router->post('/user/ban', 'V1\\Staff\\UserController@ban');
            // Plan
            $router->get ('/plan/fetch', 'V1\\Staff\\PlanController@fetch');
            // Notice
            $router->get ('/notice/fetch', 'V1\\Admin\\NoticeController@fetch');
            $router->post('/notice/save', 'V1\\Admin\\NoticeController@save');
            $router->post('/notice/update', 'V1\\Admin\\NoticeController@update');
            $router->post('/notice/drop', 'V1\\Admin\\NoticeController@drop');
            // home
            $router->get ('/info', 'V1\\Staff\\HomeController@info');
            $router->get ('/stat', 'V1\\Staff\\HomeController@stat');
            $router->get ('/config', 'V1\\Staff\\HomeController@config');
            $router->post('/configsave', 'V1\\Staff\\HomeController@configsave');
            $router->get ('/finduser', 'V1\\Staff\\HomeController@finduser');
        });
    }
}
