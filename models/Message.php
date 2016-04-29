<?php namespace Uit\Messenger\Models;

use Model;
use RainLab\User\Models\User;

/**
 * Message Model
 */
class Message extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'uit_messenger_messages';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'from',
        'to',
        'body'
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [
        'recipient' => [User::class,'key'=>'to'],
    ];

    public $belongsTo = [
        'from_info' => [User::class,'key'=>'from'],
    ];
}