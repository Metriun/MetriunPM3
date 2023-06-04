<?php

declare(strict_types=1);

namespace Metriun\Metriun\analyzers;

use Metriun\Metriun\API;
use Metriun\Metriun\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

use function array_sum;
use function count;
use function date;

class SessionAverage implements Listener {
	private $config;
	private $chart_token;

	private $sessions = [];
	private $actual_date = "";

	public function __construct(Main $plugin, $token) {
		// Registrando evento
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

		// Definindos as variaveis.
		$this->config = new Config($plugin->getDataFolder() . "data/SessionAverage.yml");
		$this->chart_token = $token;
		$this->actual_date = date("d/m/Y");

		// Iniciando a task.
		$plugin->getScheduler()->scheduleRepeatingTask(new SessionAverageTask($this), 20 * 60 * 60);
	}

	public function save() {
		// Salvar os dados do dia.
		$this->config->set($this->actual_date, $this->sessions);
		$this->config->save();
	}

	public function init() {
		// Pegar os dados guardados do dia.
		$this->sessions = $this->config->get($this->actual_date, []);
	}

	public function onJoin(PlayerJoinEvent $ev): void {
		$playerName = $ev->getPlayer()->getName();

		if (!isset($this->sessions[$playerName])) {
			$this->sessions[$playerName] = 1;
		} else {
			$this->sessions[$playerName]++;
		}
	}

	public function calculateAverage(): float {
		$totalSessions = array_sum($this->sessions);
		$uniquePlayers = count($this->sessions);

		if ($uniquePlayers === 0) {
			return 0.0;
		}

		return (float) $totalSessions / $uniquePlayers;
	}

	public function sendRequest() {
		API::request([
			$this->actual_date,
			$this->calculateAverage()
		], $this->actual_date, $this->chart_token);
	}
}

class SessionAverageTask extends Task {
	private $primary_request = false;

	public function __construct(
		private SessionAverage $owner) {
	}

	public function onRun(int $currentTick): void {
		if ($this->primary_request) {
			$this->owner->save();
			$this->owner->sendRequest();
		} else {
			$this->primary_request = true;
		}
	}
}
