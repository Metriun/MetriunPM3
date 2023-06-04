<?php

declare(strict_types=1);

namespace Metriun\Metriun;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use function method_exists;
use function mkdir;

class Main extends PluginBase {
	private $analizers = [];

	public function onEnable(): void {
		@mkdir($this->getDataFolder() . "data");

		$this->saveResource("config.yml");
		$this->reloadConfig();

		// Carregar os analisadores.
		$this->loadAnalyzers();

		foreach ($this->analizers as $analizer) {
			if (method_exists($analizer, "init")) {
				$analizer->init();
			}
		}
	}

	public function onDisable(): void {
		foreach ($this->analizers as $analizer) {
			if (method_exists($analizer, "save")) {
				$analizer->save();
			}
		}
	}

	private function loadAnalyzers(): void {
		$config = new Config($this->getDataFolder() . "config.yml", Config::YAML);

		// Máximo de jogadores por dia.
		if ($config->getNested("max-players-per-day.enable")) {
			$this->analizers[] = new analyzers\MaxPlayersPerDay($this, $config->getNested("max-players-per-day.chart_token"));
		}

		// Visitantes no dia.
		if ($config->getNested("visits-in-the-day.enable")) {
			$this->analizers[] = new analyzers\VisitsInDay($this, $config->getNested("visits-in-the-day.chart_token"));
		}

		// TPS por tempo.
		if ($config->getNested("tps-per-time.enable")) {
			$this->analizers[] = new analyzers\TpsPerTime($this, $config->getNested("tps-per-time.chart_token"), (int) $config->getNested("tps-per-time.send_time"));
		}

		// Tempo de sessão de cada jogador.
		if ($config->getNested("session-average.enable")) {
			$this->analizers[] = new analyzers\SessionAverage($this, $config->getNested("session-average.chart_token"));
		}

		// Países que acessam o servidor.
		if ($config->getNested("player-by-country.enable")) {
			$this->analizers[] = new analyzers\PlayerCountry($this, $config->getNested("player-by-country.chart_token"));
		}

		// Latência do servidor.
		if ($config->getNested("server-latency.enable")) {
			$this->analizers[] = new analyzers\ServerLatency($this, $config->getNested("server-latency.chart_token"));
		}

		// Registro de jogadores por dia.
		if ($config->getNested("player-registration-per-day.enable")) {
			$this->analizers[] = new analyzers\PlayersRegistrationsDays($this, $config->getNested("player-registration-per-day.chart_token"));
		}
	}
}
