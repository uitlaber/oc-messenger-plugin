<?php namespace Uit\Messenger\Classes\Socket;

use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use October\Rain\Support\Facades\Config;
use RainLab\User\Models\User;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Uit\Messenger\Models\Message;
use ZMQContext;

class Messenger implements MessageComponentInterface
{
    protected $clients;
    protected $members;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->bindtoSession($conn);
        $this->AttachMember($conn);

        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $json = json_decode($msg, true);
        if (array_key_exists('event', $json)) {
            switch ($json['event']) {
                case 'on.message':
                    $this->sendMessage($from, $json);
                    break;

            }
        }

    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->DettachMember($conn);
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }


    public function bindtoSession(ConnectionInterface $conn)
    {
        // Create a new session handler for this client
        $session = (new SessionManager(App::getInstance()))->driver();
        // Get the cookies
        $cookies = $conn->WebSocket->request->getCookies();
        // Get the laravel's one
        $laravelCookie = urldecode($cookies[Config::get('session.cookie')]);

        // get the user session id from it
        $idSession = Crypt::decrypt($laravelCookie);

        // Set the session id to the session handler
        $session->setId($idSession);
        // Bind the session handler to the client connection
        $conn->session = $session;

    }

    public function AttachMember(ConnectionInterface $conn)
    {
        $conn->session->start();
        $authUser = $conn->session->get('user_auth');

        if (!is_null($authUser)) {
            $this->members[$conn->resourceId] = $authUser[0];
            $user = User::find($authUser[0]);
            if (!is_null($user)) {
                foreach ($user->friends as $friend) {
                    $con_id = $this->isMemberOnline($friend->id);
                    if (!is_null($con_id)) {
                        foreach ($this->clients as $client) {
                            if ($client->resourceId == $con_id) {
                                $data = [
                                    'user_id' => $user->id,
                                    'status' => 'online',
                                    'event' => 'on.online',
                                    'info' => [
                                        'username' => $user->username,
                                        'name' => $user->name,
                                        'surname' => $user->surname,
                                        'avatar' => (!is_null($user->avatar)) ? $user->getAvatarThumb(25) : Config::get('uit.messenger::member_no_avatar')
                                    ]
                                ];
                                $client->send(json_encode($data));
                            }
                        }
                    }
                }
            }
        }
    }

    public function DettachMember(ConnectionInterface $conn)
    {
        $user = User::find($this->members[$conn->resourceId]);
        if (!is_null($user)) {
            $friends = $user->friends;
            foreach ($friends as $friend) {
                $con_id = $this->isMemberOnline($friend->id);
                if (!is_null($con_id)) {
                    foreach ($this->clients as $client) {
                        if ($client->resourceId == $con_id) {
                            $data = [
                                'user_id' => $user->id,
                                'status' => 'offline',
                                'event' => 'on.offline',
                                'info' => [
                                    'username' => $user->username,
                                    'name' => $user->name,
                                    'surname' => $user->surname,
                                    'avatar' => (!is_null($user->avatar)) ? $user->getAvatarThumb(25) : Config::get('uit.messenger::member_no_avatar')
                                ]
                            ];
                            $client->send(json_encode($data));
                        }
                    }
                }
            }
        }
        unset($this->members[$conn->resourceId]);
    }

    public function isMemberOnline($member_id)
    {
        $online = null;
        foreach ($this->members as $key => $online_member_id) {
            if ($online_member_id == $member_id) {
                $online = $key;
                continue;
            }
        }
        return $online;
    }

    public function sendMessage(ConnectionInterface $from, $json)
    {
        $to = $json['to'];
        $from_user_id = $this->members[$from->resourceId];
        $body = $json['body'];
        $time = Carbon::now()->getTimestamp();

        $data = [
            'from' => $from_user_id,
            'to' => $to,
            'body' => $body
        ];

        $rules = [
            'from' => 'required|exists:users,id',
            'to' => 'required|exists:users,id',
        ];

        $validation = Validator::make($data, $rules);


        if (!is_null($to) && $to != $from_user_id && !$validation->fails()) {
            $from_user = User::find($from_user_id);
            $con_id = $this->isMemberOnline($to);

            $message = Message::create([
                'from' => $from_user_id,
                'to' => $to,
                'body' => $body
            ]);
            if (!is_null($message)) {
                foreach ($this->clients as $client) {
                    if (!is_null($con_id) && $client->resourceId == $con_id || $client->resourceId == $from->resourceId) {
                        $msg = [
                            'id' => $message->id,
                            'body' => $body,
                            'from' => $from_user_id,
                            'event' => 'on.message_send',
                            'from_info' => [
                                'username' => $from_user->username,
                                'name' => $from_user->name,
                                'surname' => $from_user->surname,
                                'avatar' => (!is_null($from_user->avatar)) ? $from_user->getAvatarThumb(25) : Config::get('uit.messenger::member_no_avatar')
                            ],
                            'time' => $message->created_at->getTimestamp(),
                        ];
                        $client->send(json_encode($msg, true));
                    }
                }
            }


        } else {
            $msg = [
                'event' => 'on.message_send_error',
                'Ошибка при отправке сообщения'
            ];
            $this->returnError($from, $msg);
        }


    }

    public function returnError(ConnectionInterface $to, $msg)
    {
        $to->send(json_encode($msg));
    }


}