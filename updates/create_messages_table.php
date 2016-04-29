<?php namespace Uit\Messenger\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateMessagesTable extends Migration
{

    public function up()
    {
        Schema::create('uit_messenger_messages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('from')->unsigned()->index();
            $table->integer('to')->unsigned()->index();
            $table->text('body');
            $table->boolean('is_read');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('uit_messenger_messages');
    }

}
