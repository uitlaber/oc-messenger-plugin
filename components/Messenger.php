<?php namespace Uit\Messenger\Components;

use Config;

use Validator;
use ValidationException;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use Uit\Messenger\Classes\Socket\Messenger as MessengerSocket;
use Uit\Messenger\Models\Message as MessageModel;

class Messenger extends ComponentBase
{

    public $wsurl;
    public $friends;


    public function componentDetails()
    {
        return [
            'name'        => 'Messenger Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
            'wsurl' => [
                'title'       => 'uit.mychat::lang.topics.wsurl',
                'description' => 'uit.mychat::lang.topics.wsurl_description',
                'type'        => 'string',
                'default'     => 'ws://chat.dev:8080'
            ]
        ];
    }

    public function onRun()
    {
        if(Auth::check()){

            $user = Auth::getUser();
            $this->wsurl = $this->property('wsurl');
            $this->friends = $user->friends();
        }

    }

    public function onGetPrivateMessage()
    {
        if(!Auth::check()) return;
        $user = Auth::getUser();
        $data = post();
        $rules = [
            'user_id' => 'required|exists:users,id'
        ];
        $validation = Validator::make($data, $rules);

        if($validation->fails()){
            throw new ValidationException($validation);
        }
        $dbmessages = [];

        $messages =  MessageModel::with('from_info')
            ->where('from',$user->id)->where('to',$data['user_id'])
            ->orWhere('to',$user->id)->where('from',$data['user_id'])
            ->take(20)
            ->get();

        foreach ($messages as $message){
            $dbmessages[] = [
               'id'=>$message->id,
               'from' => $message->from,
               'to' => $message->to,
               'body' => $message->body,
               'time' => $message->created_at->getTimestamp(),
               'from_info' => [
                   'id' => $message->from_info->id,
                   'username' => $message->from_info->username,
                   'name' => $message->from_info->name,
                   'surname' => $message->from_info->surname,
                   'avatar' => (!is_null($message->from_info->avatar))?$message->from_info->getAvatarThumb(25) : Config::get('uit.messenger::member_no_avatar')
               ]
            ] ;
        }



        return  $dbmessages ;
    }

    public function onGetFriends()
    {
        if(!Auth::check()) return;
        $friends = null;
        $user = Auth::getUser();
        foreach ($user->friends as $friend){
            $friends[$friend->id] = [
                'id' => $friend->id,
                'username' => $friend->username,
                'name' => $friend->name,
                'surname' => $friend->surname,
                'avatar' => (!is_null($friend->avatar))?$friend->getAvatarThumb(25) : Config::get('uit.messenger::member_no_avatar')
            ];
        }
        return $friends;
    }

}