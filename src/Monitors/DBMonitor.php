<?php
namespace Shieldfy\Monitors;

use Shieldfy\Jury\Judge;

class DBMonitor extends MonitorBase
{
    use Judge;

    protected $name = "db";
    protected $infected = [];

    /**
     * run the monitor
     */
    public function run()
    {
        $this->events->listen('db.query', function ($query, $bindings = []) {
            call_user_func([
                $this,
                'analyze'
            ], $query, $bindings);
        });
    }


    public function analyze($query, $bindings = [])
    {

        //get the parameters (all)
        $request = $this->collectors['request'];
        $info = $request->getInfo();

        $params = array_merge($info['get'], $info['post']);

        $foundGuilty = false;
        $charge = [];
        $this->issue('db');
        foreach ($params as $key => $value) {
            if (stripos($query, $value) !== false || in_array($value, $bindings)) {

                //found parameter
                //check infection
                $charge = $this->sentence($value);
                if ($charge && $charge['score']) {
                    $foundGuilty = true;
                    $charge['value'] = $value;
                    $charge['key'] = $key;
                    break;
                }
            }
        }

        if ($foundGuilty) {
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $code = $this->collectors['code']->pushStack($stack)->collectFromStack($stack);
            $this->sendToJail($this->parseScore($charge['score']), $charge, $code);
        }
    }
}
