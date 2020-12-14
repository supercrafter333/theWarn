<?php

namespace supercrafter333\theWarn;

use DateTime;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class theWarn extends PluginBase implements Listener {

    public function onEnable()
    {
        $this->saveResource("messages.yml");
        $this->saveResource("banconfig.yml");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        $banconfig = new Config($this->getDataFolder() . "banconfig.yml", Config::YAML);
        if (!$messages->exists("version") && !$messages->get("version") == "1.3") {
            $this->getServer()->getLogger()->error("<theWarn> !!OUTDATED CONFIG!! Please delete the file »messages.yml« and restart your server!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        if (!$banconfig->exists("version") && !$banconfig->get("version") == "1.3") {
            $this->getServer()->getLogger()->error("<theWarn> !!OUTDATED CONFIG!! Please delete the file »banconfig.yml« and restart your server!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    public function onJoin(PlayerJoinEvent $event) {
        $config = new Config($this->getDataFolder()."warns.yml", Config::YAML);
        if (!$config->exists($event->getPlayer()->getName())) {
            $config->set($event->getPlayer()->getName(), "0");
            $config->save();
        }
    }

    public function onCommand(CommandSender $s, Command $cmd, string $label, array $args): bool
    {
        $prefix = "§f[§7the§dWarn§f] §8»§r ";
        $config = new Config($this->getDataFolder() . "warns.yml", Config::YAML);
        $messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        $banconfig = new Config($this->getDataFolder() . "banconfig.yml", Config::YAML);
        if ($cmd->getName() === "thewarn") {
            if (!$s->hasPermission("thewarn.warn.cmd")) {
                $s->sendMessage($prefix . $messages->get("missing-permissions-message"));
                return true;
            }
            if (count($args) < 2) {
                $s->sendMessage($prefix . $messages->get("usage-message"));
                return false;
            }
            $warnplayer = $this->getServer()->getPlayer($args[0]);
            if ($warnplayer !== null) {
                $s->sendMessage($prefix . $messages->get("invalid-playername-message"));
                return true;
            }
            $warnplayername = $warnplayer->getName();
            if ($config->get($warnplayername) < $banconfig->get("warn-count")) {
                $config->set($warnplayername, intval($config->get($warnplayername) + 1));
                $config->save();
                $warnplayer->sendMessage(str_replace(["{player}"], [$s->getName()], str_replace(["{warnplayer}"], [$warnplayername], str_replace(["{warnnumber}"], [$config->get($warnplayername)], $prefix . $messages->get("warn-message")))));
                $s->sendMessage(str_replace(["{warnplayer}"], [$warnplayername], str_replace(["{warnnumber}"], [$config->get($warnplayername)], $prefix . $messages->get("player-was-warned-message"))));
            } elseif ($config->get($warnplayername) >= $banconfig->get("warn-count")) {
                $this->getServer()->getNameBans()->addBan($warnplayername, $messages->get("ban-kick-message"), new DateTime('+' . $banconfig->get("banned-days") . ' days'), $s->getName());
                $warnplayer->kick(str_replace(["{player}"], [$s->getName()], $messages->get("ban-kick-message")), false);
                $config->set($warnplayername, "banned");
                $config->save();
                $s->sendMessage(str_replace(["{warnplayer}"], [$warnplayer->getName()], $prefix . $messages->get("player-was-banned-message")));
            }
        }elseif ($cmd->getName() === "thewarnunban") {
            if (!$s->hasPermission("thewarn.thewarnunban.cmd")) {
                $s->sendMessage($prefix . $messages->get("missing-permissions-message"));
                return true;
            }
            if (count($args) < 1) {
                $s->sendMessage(str_replace(["{player}"], [$args[0]], $prefix . $messages->get("manual-unban-usage-message")));
                return false;
            }
            if (!$config->exists($args[0]) || !$this->getServer()->getNameBans()->isBanned($args[0])) {
                $s->sendMessage(str_replace(["{player}"], [$args[0]], $prefix . $messages->get("manual-unban-invalid-playername-message")));
                return true;
            }
            $config->remove($args[0]);
            $config->save();
            $this->getServer()->getNameBans()->remove($args[0]);
            $s->sendMessage(str_replace(["{player}"], [$args[0]], $prefix . $messages->get("manual-unban-message")));
        }
        return true;
    }

    public function onPreLogin(PlayerPreLoginEvent $event) {
        $playername = $event->getPlayer()->getName();
        $config = new Config($this->getDataFolder()."warns.yml", Config::YAML);
        if ($config->get($playername, null) === "banned" && !$this->getServer()->getNameBans()->isBanned($playername)) {
            $config->remove($playername);
            $config->save();
        }
    }
}
