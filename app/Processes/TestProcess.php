<?php

namespace App\Processes;

use App\Tasks\TestTask;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Redis;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Process;

class TestProcess implements CustomProcessInterface
{
    public static function getName()
    {
        // The name of process
        return 'test';
    }

    public static function callback(Server $swoole, Process $process)
    {
        // The callback method cannot exit. Once exited, Manager process will automatically create the process 
        \Log::info(__METHOD__, [posix_getpid(), $swoole->stats()]);

        while (true) {
            // \Log::info('Do something');
            // sleep(1); // Swoole < 2.1
            Coroutine::sleep(2); // Swoole>=2.1 Coroutine will be automatically created for callback().
            // Deliver task in custom process, but NOT support callback finish() of task.
            // Note:
            // 1.Set parameter 2 to true
            if (1 == Redis::exists('s')) {

                $ret = Task::deliver(new TestTask('task data process'));
                var_dump($ret);
            }

            // 2.Modify task_ipc_mode to 1 or 2 in config/laravels.php, see https://www.swoole.co.uk/docs/modules/swoole-server/configuration
            // The upper layer will capture the exception thrown in the callback and record it to the Swoole log. If the number of exceptions reaches 10, the process will exit and the Manager process will re-create the process. Therefore, developers are encouraged to try/catch to avoid creating the process too frequently.
            // throw new \Exception('an exception');
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        // Stop the process...
        // Then end process
        $process->exit(0);
    }
}
