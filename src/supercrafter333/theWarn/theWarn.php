<?php

namespace supercrafter333\theWarn;

use DateInterval;
use DateTime;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class theWarn extends PluginBase implements Listener {

    public function onEnable()
    {
        $this->saveResource("messages.yml");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onJoin(PlayerJoinEvent $event) {
        $config = new Config($this->getDataFolder()."warns.yml", Config::YAML);
        if (!$event->getPlayer()->hasPlayedBefore() || !$config->exists($event->getPlayer()->getName())) {
            $config->set($event->getPlayer()->getName(), "0");
            $config->save();
        }
    }

    public function onCommand(CommandSender $s, Command $cmd, string $label, array $args): bool
    {
        $prefix = "§f[§7the§dWarn§f] §8»§r ";
        $name = $s->getName();
        $config = new Config($this->getDataFolder() . "warns.yml", Config::YAML);
        $messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        if ($cmd->getName() == "thewarn") {
            if ($s->hasPermission("thewarn.warn.cmd")) {
                if (count($args) >= 2) {
                    $warnplayer = $this->getServer()->getPlayer($args[0]);
                    if (!$warnplayer == null) {
                        $warnplayername = $warnplayer->getName();
                        if ($config->get($warnplayername) < 3) {
                            $config->set($warnplayername, intval($config->get($warnplayername) + 1));
                            $config->save();
                            $warnplayer->sendMessage(str_replace(["{player}"], [$s->getName()], str_replace(["{warnplayer}"], [$warnplayername], str_replace(["{warnnumber}"], [$config->get($warnplayername)], $prefix . $messages->get("warn-message")))));
                            $s->sendMessage(str_replace(["{warnplayer}"], [$warnplayername], str_replace(["{warnnumber}"], [$config->get($warnplayername)], $prefix . $messages->get("player-was-warned-message"))));
                            return true;
                        } elseif ($config->get($warnplayername) >= 3) {
                            $ban = new Config($this->getDataFolder() . "bans.yml", Config::YAML);
                            $warnplayer->kick(str_replace(["{player}"], [$s->getName()], $messages->get("ban-kick-message")), false);
                            $config->set($warnplayername, "banned");
                            $config->save();
                            $bantime = new DateTime('+7 days');
                            $bt = $bantime->format('Y-m-d H:i:s');
                            $ban->set($warnplayername, $bt);
                            $ban->save();
                            $s->sendMessage(str_replace(["{warnplayer}"], [$warnplayer->getName()], $prefix . $messages->get("player-was-banned-message")));
                            return true;
                        }
                    } else {
                        $s->sendMessage($prefix . $messages->get("invalid-playername-message"));
                        return true;
                    }
                } else {
                    $s->sendMessage($prefix . $messages->get("usage-message"));
                    return true;
                }
            } else {
                $s->sendMessage($prefix . $messages->get("missing-permissions-message"));
                return true;
            }
        }
        return true;
    }

    public function onPreLogin(PlayerPreLoginEvent $event) {
        $player = $event->getPlayer();
        $playername = $player->getName();
        $ban = new Config($this->getDataFolder()."bans.yml", Config::YAML);
        $config = new Config($this->getDataFolder()."warns.yml", Config::YAML);
        $messages = new Config($this->getDataFolder()."messages.yml", Config::YAML);
        $now = new DateTime("now");
        if ($config->exists($playername)) {
            if ($config->get($playername) == "banned") {
                $bantimer = new DateTime($ban->get($playername));
                if ($bantimer > $now) {
                    $player->close("", $messages->get("you-are-banned-message"));
                } elseif ($bantimer < $now) {
                    $event->setCancelled(false);
                }
            } else {
                $event->setCancelled(false);
            }
        } else {
            $event->setCancelled(false);
        }
    }
}
