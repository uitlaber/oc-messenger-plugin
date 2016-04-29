<?php namespace Uit\Messenger\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateFriendshipsTable extends Migration
{

    public function up()
    {
        Schema::create('uit_messenger_friendships', function($table)
        {
            $table->integer('friend_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('friend_id')->references('id')->on('users');

            $table->primary(array('user_id', 'friend_id'));

        });
    }

    public function down()
    {
        Schema::dropIfExists('uit_messenger_friendships');
    }

}
