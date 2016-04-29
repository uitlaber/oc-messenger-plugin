<?php namespace Uit\Messenger;

use Backend;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use Uit\Messenger\Models\Message;

/**
 * messenger Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'messenger',
            'description' => 'No description provided yet...',
            'author'      => 'uit',
            'icon'        => 'icon-leaf'
        ];
    }


    public function register()
    {
        $this->registerConsoleCommand('messenger.serve', 'Uit\Messenger\Console\RunCommand');
    }


    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Uit\Messenger\Components\Messenger' => 'Messenger',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'uit.messenger.some_permission' => [
                'tab' => 'messenger',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'messenger' => [
                'label'       => 'messenger',
                'url'         => Backend::url('uit/messenger/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['uit.messenger.*'],
                'order'       => 500,
            ],
        ];
    }

    public function boot()
    {
       User::extend(function($model){
           $model->belongsToMany = ['friends' => [User::class,'table'=>'uit_messenger_friendships','key'=>'friend_id','delete'=>true]];
           $model->addDynamicMethod('addFriend', function($user_id) use ($model)   {
                $model->friends()->attach($user_id);
           });
           $model->addDynamicMethod('removeFriend',  function($user_id) use ($model)  {
               $model->friends()->detach($user_id);
           });
       });
    }

}
