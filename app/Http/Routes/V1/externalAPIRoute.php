<?php
namespace App\Http\Routes\V1;

use Illuminate\Contracts\Routing\Registrar;

class externalAPIRoute
{
    public function map(Registrar $router)
    {
        $router->group([
            'prefix' => 'externalAPI',
            'middleware' => 'externalAPI'
        ], function ($router) {
            //邀请码
            $router->get('/APi/Invitationcode', 'V1\\externalAPI\\externalAPIController@Invitationcode');
            $router->get('/APi/Accountbanquery', 'V1\\externalAPI\\externalAPIController@Accountbanquery');

        });
    }
}