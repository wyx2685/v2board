<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

abstract class LocalizedCommand extends Command
{
    /**
     * Translation key used for the command description.
     *
     * @var string|null
     */
    protected $descriptionKey;

    /**
     * Create a new localized command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        if ($this->descriptionKey) {
            $this->description = (string)__($this->descriptionKey);
            $this->setDescription($this->description);
        }
    }
}
