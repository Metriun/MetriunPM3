<?php

declare(strict_types=1);

namespace Metriun\Metriun\analyzers;

use Metriun\Metriun\API;
use Metriun\Metriun\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\Config;

use function count;
use function date;

class MaxPlayersPerDay implements Listener {
    private $config;
    private $peak_players = 0;
    private $chart_token;
    private $plugin;
    private $actual_date = "";

    public function __construct(Main $plugin, string $token) {
        // Registrando evento
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

        // Definindo as variáveis
        $this->config = new Config($plugin->getDataFolder() . "data/MaxPlayersPerDay.yml", Config::YAML);
        $this->chart_token = $token;
        $this->plugin = $plugin;
        $this->actual_date = date("d/m/Y");

        // Iniciando a tarefa
        $plugin->getScheduler()->scheduleRepeatingTask(new MaxPlayerTask($this), 20 * 60 * 60);
    }

    public function save() : void {
        // Salvar os dados do dia
        $this->config->set($this->actual_date, $this->peak_players);
        $this->config->save();
    }

    public function init() : void {
        // Pegar os dados guardados do dia
        $this->peak_players = $this->config->get($this->actual_date, 0);
    }

    public function onJoin(PlayerJoinEvent $ev) : void {
        $online_players = count($this->plugin->getServer()->getOnlinePlayers());

        if ($online_players > $this->peak_players) {
            $this->peak_players = $online_players;
        }
    }

    public function sendRequest() : void {
        API::request([
            $this->actual_date,
            $this->peak_players
        ], $this->actual_date, $this->chart_token);
    }
}

class MaxPlayerTask extends Task {
    private $primary_request = false;
    private $owner;

    public function __construct(MaxPlayersPerDay $owner) {
        $this->owner = $owner;
    }

    public function onRun(int $currentTick) : void {
        if ($this->primary_request) {
            $this->owner->save();
            $this->owner->sendRequest();
        } else {
            $this->primary_request = true;
        }
    }
}
