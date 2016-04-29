<?php namespace Uit\Messenger\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Uit\Messenger\Classes\Socket\Messenger;

class RunCommand extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'messenger:serve';

    /**
     * @var string The console command description.
     */
    protected $description = 'Runs the WebSocket server.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $port = '8080';
        if(!is_null($this->option('port'))){
            $port = $this->option('port');
        }

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new Messenger()
                )
            ),
            $port
        );

        $server->run();

    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['port', null, InputOption::VALUE_OPTIONAL, 'WS server port.', 8080],
        ];
    }

}
