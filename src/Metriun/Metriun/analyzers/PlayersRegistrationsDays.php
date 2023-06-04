<?php

declare(strict_types=1);

namespace Metriun\Metriun\analyzers;

use Metriun\Metriun\API;
use Metriun\Metriun\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

use function date;

class PlayersRegistrationsDays implements Listener {
    private $config;
    private $chart_token;

    private $first_joins = 0;
    private $actual_date = "";

    public function __construct(Main $plugin, $token) {
        // Registrando evento
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

        // Definindos as variaveis.
        $this->config = new Config($plugin->getDataFolder() . "data/PlayersRegistrationsDays.yml", Config::YAML);
        $this->chart_token = $token;
        $this->actual_date = date("d/m/Y");

        // Iniciando a task.
        $plugin->getScheduler()->scheduleRepeatingTask(new PlayersRegistrationsDaysTask($this), 20 * 60 * 45);
    }

    public function save() {
        // Salvar os dados do dia.
        $this->config->set($this->actual_date, $this->first_joins);
        $this->config->save();
    }

    public function init() {
        // Pegar os dados guardados do dia.
        $this->first_joins = (int) $this->config->get($this->actual_date, 0);
    }

    public function onJoin(PlayerJoinEvent $ev) {
        if (!$ev->getPlayer()->hasPlayedBefore()) {
            $this->first_joins++;
        }
    }

    public function sendRequest() {
        API::request([
            $this->actual_date,
            $this->first_joins
        ], $this->actual_date, $this->chart_token);
    }
}

class PlayersRegistrationsDaysTask extends Task {
    private $primary_request = false;

    public function __construct(private PlayersRegistrationsDays $owner) {
    }

    public function onRun(int $currentTick) {
        if ($this->primary_request) {
            $this->owner->sendRequest();
        } else {
            $this->primary_request = true;
        }
    }
}
