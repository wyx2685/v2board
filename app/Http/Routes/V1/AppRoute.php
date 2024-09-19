<?php
#move to app/Http/Routes/
#v2board.app
namespace App\Http\Routes\V1;

use Illuminate\Contracts\Routing\Registrar;

class AppRoute
{
    public function map(Registrar $router)
    {
        $router->group([
            'prefix' => 'app'
        ], function ($router) {
            $router->get('/appnotice', 'V1\\AppClient\\AppController@appnotice');
            $router->get('/appknowledge', 'V1\\AppClient\\AppController@appknowledge');
            $router->post('/applogin', 'V1\\AppClient\\AppController@applogin');
            $router->post('/appsendEmailVerify', 'V1\\AppClient\\AppController@appsendEmailVerify');
            $router->post('/appforget', 'V1\\AppClient\\AppController@appforget');
            $router->post('/appregister', 'V1\\AppClient\\AppController@appregister');
            $router->post('/appsync','V1\\AppClient\\AppController@appsync');
            $router->post('/appalert', 'V1\\AppClient\\AppController@appalert');
            $router->post('/accountdelete','V1\\AppClient\\AppController@appDelete');
            $router->post('/getTempToken', 'V1\\AppClient\\AppController@getTempToken');
            $router->get ('/config', 'V1\\AppClient\\AppController@appconfig');
            $router->post('/appupdate', 'V1\\AppClient\\AppController@appupdate');
            $router->get('/homepage', 'V1\\AppClient\\AppController@token2Login');
            $router->post('/appalert', 'V1\\AppClient\\AppController@appalert');
            //adds
            $router->post('/orderdetail', 'V1\\AppClient\\AppController@orderdetail');
            $router->post('/checktrade', 'V1\\AppClient\\AppController@checktrade');
            $router->post('/ordercancel', 'V1\\AppClient\\AppController@ordercancel');
            $router->post('/checkout', 'V1\\AppClient\\AppController@checkout');
            $router->post('/ordersave', 'V1\\AppClient\\AppController@ordersave');
            $router->post('/appinvite', 'V1\\AppClient\\AppController@appinvite');
            $router->post('/couponCheck', 'V1\\AppClient\\AppController@couponCheck');
            $router->get('/apppaymentmethod', 'V1\\AppClient\\AppController@getPaymentMethod');
            $router->get('/appshop', 'V1\\AppClient\\AppController@appshop');
        });
    }
}
