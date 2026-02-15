<?php

namespace morskoi\ExpExchange;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use morskoi\ExpExchange\command\ExpCommand;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\utils\Config;

class ExpExchange extends PluginBase implements Listener {
    private Config $cfg;
    public function onEnable(): void {
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        $this->saveResource("config.yml");
        $this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->configData = $this->getConfig()->getAll();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("Exp", new ExpCommand($this));
    }
    public function getConfig(): Config 
    {
        return $this->cfg;
    }
    public function ExpMenu(Player $p) {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName($this->cfg->get("menu-name"));
        $inv = $menu->getInventory();
        $item = VanillaItems::EXPERIENCE_BOTTLE();
        $item2 = VanillaItems::EXPERIENCE_BOTTLE();
        $item3 = VanillaItems::EXPERIENCE_BOTTLE();
        $item->setCustomName($this->cfg->get("15-xp-name"));
        $item2->setCustomName($this->cfg->get("30-xp-name"));
        $item3->setCustomName($this->cfg->get("45-xp-name"));
        $item->setLore([$this->cfg->get("15-xp-lore")]);
        $item2->setLore([$this->cfg->get("30-xp-lore")]);
        $item3->setLore([$this->cfg->get("45-xp-lore")]);
        $item->getNamedTag()->setString("xp_type", "15");
        $item2->getNamedTag()->setString("xp_type", "30");
        $item3->getNamedTag()->setString("xp_type", "45");
        $inv->setItem(11, $item);
        $inv->setItem(13, $item2);
        $inv->setItem(15, $item3);
        $menu->setListener(function (InvMenuTransaction $transaction): InvMenuTransactionResult 
        {
            $p = $transaction->getPlayer();
            $item = $transaction->getItemClicked();
            $xpType = $item->getNamedTag()->getString("xp_type", null);
            
            if ($xpType === null) {
                return $transaction->discard();
            }
            $requiredLevel = (int) $xpType;
            $errorMsg = str_replace("{LEVEL}", (string) $requiredLevel, $this->configData["message-not-xp"]);

            if ($p->getXpManager()->getXpLevel() < $requiredLevel) {
                $p->sendMessage($errorMsg);
                return $transaction->discard();
            }
            $bottle = VanillaItems::GLASS_BOTTLE();
            $inv = $p->getInventory();
            
            if (!$inv->contains($bottle)) {
                $p->sendMessage($this->cfg->get("message-not-bottle"));
                return $transaction->discard();
            }

            $inv->removeItem($bottle->setCount(1));
            
            $p->getXpManager()->subtractXpLevels($requiredLevel);
            $expBottle = VanillaItems::EXPERIENCE_BOTTLE();
            $name = str_replace("{LEVEL}", $requiredLevel, $this->configData["bottle-name"]);
            $lore = str_replace("{LEVEL}", $requiredLevel, $this->configData["bottle-lore"]);

            $expBottle->setCustomName($name);
            $expBottle->setLore([$lore]);
            $expBottle->getNamedTag()->setString("exp_bottle", (string) $requiredLevel);
            
            $p->getInventory()->addItem($expBottle);
            $successMsg = str_replace("{LEVEL}", (string) $requiredLevel, $this->configData["message-give"]);
            $p->sendMessage($successMsg);
            
            return $transaction->discard();
        });
        $menu->send($p);
    }
    public function onItemUse(PlayerItemUseEvent $e): void {
        $p = $e->getPlayer();
        $item = $e->getItem();
        
        $namedtag = $item->getNamedTag()->getString("exp_bottle", "");
        
        $xp = match($namedtag) {
            "15" => 15,
            "30" => 30,
            "45" => 45,
            default => 0
        };
        if ($xp === 0) return;
        
        $e->cancel();
        
        $p->getXpManager()->addXpLevels($xp);
        
        $item->pop();
        $p->getInventory()->setItemInHand($item);
    }
}

