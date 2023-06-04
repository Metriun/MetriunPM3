<?php

declare(strict_types=1);

namespace Metriun\Metriun\analyzers;

use Metriun\Metriun\API;
use Metriun\Metriun\Main;
use pocketmine\scheduler\Task;

use function count;
use function date;
use function round;

class ServerLatency {
    private $chart_token;
    private $plugin;

    public function __construct(Main $plugin, $token) {
        $this->chart_token = $token;
        $this->plugin = $plugin;

        $this->plugin->getScheduler()->scheduleRepeatingTask(new ServerLatencyTask($this), 20 * 60 * 120);
    }

    public function getServerLatency(): int {
        $players = $this->plugin->getServer()->getOnlinePlayers();
        $totalPing = 0;
        $playerCount = count($players);

        foreach ($players as $player) {
            $ping = $player->getPing();
            $totalPing += $ping;
        }

        if ($playerCount > 0) {
            $averagePing = $totalPing / $playerCount;
            return (int) round($averagePing);
        }

        return 0;
    }

    public function sendRequest() {
        $data = date("m/Y");
        $server_latency = $this->getServerLatency();

        API::request([$data, $server_latency], $data, $this->chart_token);
    }
}

class ServerLatencyTask extends Task {
    private $primary_request = false;
    private $owner;

    public function __construct(ServerLatency $owner) {
        $this->owner = $owner;
    }

    public function onRun(int $currentTick): void {
        if ($this->primary_request) {
            $this->owner->sendRequest();
        } else {
            $this->primary_request = true;
        }
    }
}
