<?php

/*
 * 
 * u gay lmao
 * 
 */

namespace aytgdmr1\AuthUI;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\level\sound\FizzSound;
use pocketmine\level\sound\PopSound;
use pocketmine\math\Vector3;
use aytgdmr1\AuthUI\libs\jojoe77777\FormAPI\CustomForm;


class AuthUI extends PluginBase implements Listener {
    
    public function onEnable() {
        $this->auth = $this->getServer()->getPluginManager()->getPlugin("SimpleAuth");
        if (!$this->auth) {
            $this->getLogger()->error(TextFormat::RED.("RegisterUI couldn't loaded."));
            $this->getLogger()->error(TextFormat::RED.("Unable to find SimpleAuth plugin"));
            return;
        }
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        $this->saveDefaultConfig();
        $this->reloadConfig();
        
        $this->getLogger()->info("AuthUI successfully loaded.");
    }
    
    # Thanks to the SimpleAuthHelper developers for this function.
    private function hash($salt, $password){
	return bin2hex(hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true));
    }
    public function authenticate($pl,$password) {
        $provider = $this->auth->getDataProvider();
        $data = $provider->getPlayerData($pl->getName());
        
        return hash_equals($data["hash"], $this->hash(strtolower($pl->getName()), $password));
        
    }
    
    public function loginForm($player){
        $level = $player->getLevel();
        $x = $player->getX();
        $y = $player->getY();
        $z = $player->getZ();
        $pos = new Vector3($x, $y, $z);
        
        $form = new CustomForm(function (Player $player, $data) use ($level, $pos){
            
            if (!$data[1]){
                return $player->kick(TextFormat::GREEN . TextFormat::BOLD . $this->getConfig()->get("login-form-empty-password-field"));
            }
            
            if ($this->authenticate($player, $data[1])){
                $level->addSound(new PopSound($pos));
                $this->auth->authenticatePlayer($player);
            } else {
                $level->addSound(new FizzSound($pos));
                $this->loginForm($player);
            }


        });
        $form->setTitle(TextFormat::GREEN . TextFormat::BOLD . $this->getConfig()->get("login-form-title"));
        $form->addLabel(TextFormat::BOLD . TextFormat::AQUA . $this->getConfig()->get("login-form-text1"));
        $form->addInput($this->getConfig()->get("login-form-text2"), $this->getConfig()->get("login-form-password-field"));
        $form->sendToPlayer($player);
    }
    
    public function registerForm($player){
        $form = new CustomForm(function (Player $sender, $data) use ($player){
            if (!$data[1]){
                if (!$data[1]){
                    return $player->kick(TextFormat::GREEN . TextFormat::BOLD . $this->getConfig()->get("register-form-empty-password-field"));
                }
            } else {
                $this->auth->registerPlayer($sender, $data[1]);
                if ($this->auth->isPlayerRegistered($sender)){
                    $this->auth->authenticatePlayer($sender);
                };
            }
        });
        $form->setTitle(TextFormat::GREEN . TextFormat::BOLD . $this->getConfig()->get("register-form-title"));
        $form->addLabel(TextFormat::BOLD . TextFormat::AQUA . $this->getConfig()->get("register-form-text1"));
        $form->addInput($this->getConfig()->get("register-form-text2"), $this->getConfig()->get("register-form-password-field"));
        $form->sendToPlayer($player);
    }
    
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        
        if(!$this->auth->isPlayerRegistered($player)){
            $this->registerForm($player);
           
        } else if ($this->auth->isPlayerRegistered($player) && !$this->auth->isPlayerAuthenticated($player)){
            $this->loginForm($player);
            
        }
    }
    
    public function onCommand(CommandSender $sender,Command $cmd, string $label, array $args) : bool{
        if ($cmd->getName() === "authui"){
            $sender->sendMessage(TextFormat::GREEN . "Badly coded by AytgDmr1");
        }
        return true;
    }
}