<?php

namespace morskoi\ExpExchange\command;

use pocketmine\command\{Command, CommandSender};
use pocketmine\player\Player;
use morskoi\ExpExchange\ExpExchange;
use pocketmine\scheduler\ClosureTask;

class ExpCommand extends Command
{
    private ExpExchange $plugin;

    public function __construct(ExpExchange $plugin)
    {
        $this->plugin = $plugin;
        parent::__construct($this->plugin->getConfig()->get("command-name"), $this->plugin->getConfig()->get("command-description"), null, $this->plugin->getConfig()->get("command-aliases"));
        $this->setPermission("exp.cmd");
    }
    public function execute(CommandSender $s, string $label, array $args)
    {
        if (!$s instanceof Player)
        {
            $s->sendMessage("Only in game");
            return;
        }
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function() use ($s): void {
                    if ($s->isOnline()) {
                        $this->plugin->ExpMenu($s);
                    }
                }
            ), 20);
    }
}
