<?php

declare(strict_types=1);

namespace Metriun\Metriun\analyzers;

use Metriun\Metriun\API;
use Metriun\Metriun\Main;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

use function date;

class TpsPerTime {
	private $chart_token;
	private $plugin;
	private $actual_date = "";

	public function __construct(Main $plugin, $token, $send_time) {
		// Verificar o tempo
		if ($send_time < 3600) {
			$plugin->getServer()->getLogger()->error(TextFormat::RED . "O send_time do tps-per-time requer maior ou igual a 3600.");
			return;
		}

		// Definindos as variaveis.
		$this->chart_token = $token;
		$this->plugin = $plugin;
		$this->actual_date = date("d/m/Y - H:i");

		// Iniciando a task.
		$plugin->getScheduler()->scheduleRepeatingTask(new TpsPerTimeTask($this), 20 * $send_time);
	}

	public function sendRequest() {
		API::request([
			$this->actual_date,
			$this->plugin->getServer()->getTicksPerSecond()
		], false, $this->chart_token);
	}
}

class TpsPerTimeTask extends Task {
	private $primary_request = false;

	public function __construct(
		private TpsPerTime $owner) {
	}

	public function onRun(int $currentTick): void {
		if ($this->primary_request) {
			$this->owner->sendRequest();
		} else {
			$this->primary_request = true;
		}
	}
}
