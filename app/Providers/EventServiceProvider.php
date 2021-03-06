<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Create_AD_IPPhone_Event' => [
            'App\Listeners\Create_AD_IPPhone_Listener',
        ],
        'App\Events\Update_IDM_PhoneNumber_Event' => [
            'App\Listeners\Update_IDM_PhoneNumber_Listener',
        ],
        'App\Events\Create_Cucm_Local_EndUser_Event' => [
            'App\Listeners\Create_Cucm_Local_EndUser_Listener',
        ],
        'App\Events\Create_Line_Event' => [
            'App\Listeners\Create_Line_Listener',
        ],
        'App\Events\Create_Phone_Event' => [
            'App\Listeners\Create_Phone_Listener',
        ],
        'App\Events\Create_UnityConnection_Mailbox_Event' => [
            'App\Listeners\Create_UnityConnection_Mailbox_Listener',
        ],
        'App\Events\Create_UnityConnection_LDAP_Import_Mailbox_Event' => [
            'App\Listeners\Create_UnityConnection_LDAP_Import_Mailbox_Listener',
        ],
        'App\Events\Update_Teams_User_For_Voice_Event' => [
            'App\Listeners\Update_Teams_User_For_Voice_Listener',
        ],
        'App\Events\Update_Cucm_CallForward_To_Teams_Event' => [
            'App\Listeners\Update_Cucm_CallForward_To_Teams_Listener',
        ],

    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
