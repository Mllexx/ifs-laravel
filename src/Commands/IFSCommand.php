<?php

namespace Mllexx\IFS\Commands;

use Illuminate\Console\Command;

class IFSCommand extends Command
{
    public $signature = 'ifs:test';

    public $description = 'IFS command';

    public function handle(): int
    {
        $this->comment(config('ifs.command_output'));

        return self::SUCCESS;
    }
}
