<?php namespace Uit\Messenger\Models;

use Model;

/**
 * Friendship Model
 */
class Friendship extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'uit_messenger_friendships';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

}