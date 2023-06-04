<?php

declare(strict_types=1);

namespace Metriun\Metriun\analyzers;

use Metriun\Metriun\API;
use Metriun\Metriun\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use SplDoublyLinkedList;

use function current;
use function date;
use function key;

class PlayerCountry implements Listener {
    private $players;
    private $chart_token;
    private $config;

    private $plugin;

    public function __construct(Main $plugin, $token) {
        // Registrando evento
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

        $this->chart_token = $token;

        $this->config = new Config($plugin->getDataFolder() . "data/PlayerCountry.yml", Config::YAML);

        $this->players = new SplDoublyLinkedList();
        $this->plugin = $plugin;
    }

    public function save() {
        $ps = [];
        foreach ($this->players as $k => $v) {
            $ps[$k] = $v;
        }
        $this->config->setAll([date("m/Y") => $ps]);
        $this->config->save();
    }

    public function init() {
        $players = $this->config->getAll()[date("m/Y")] ?? [];
        foreach ($players as $date => $data) {
            $this->players->push($data);
        }
        $this->players->rewind();
        // Iniciando a tarefa periódica.
        $this->plugin->getScheduler()->scheduleRepeatingTask(new PlayerCountryTask($this), 20 * 60 * 90);
    }

    public function onJoin(PlayerJoinEvent $ev) {
        $player = $ev->getPlayer();
        $ip = $player->getAddress();
        $localeToCountry = [
            'pt_BR' => 'BR', // Brasil
            'pt_PT' => 'PT', // Portugal
            'en_US' => 'US', // Estados Unidos
            'hi_IN' => 'IN', // Índia
            'zh_CN' => 'CN', // China
            'es_MX' => 'MX', // México
            'ar_SA' => 'SA', // Arábia Saudita
            'ru_RU' => 'RU', // Rússia
            'fr_FR' => 'FR', // França
            'ja_JP' => 'JP', // Japão
            'en_GB' => 'GB', // Reino Unido
            'de_DE' => 'DE', // Alemanha
            'pt_AO' => 'AO', // Angola
            'bn_BD' => 'BD', // Bangladesh
            'ko_KR' => 'KR', // Coreia do Sul
            'es_ES' => 'ES', // Espanha
            'id_ID' => 'ID', // Indonésia
            'tr_TR' => 'TR', // Turquia
            'vi_VN' => 'VN', // Vietnã
            'it_IT' => 'IT', // Itália
            'pl_PL' => 'PL', // Polônia
            'uk_UA' => 'UA', // Ucrânia
            'th_TH' => 'TH', // Tailândia
            'ro_RO' => 'RO', // Romênia
            'nl_NL' => 'NL', // Países Baixos
            'hu_HU' => 'HU', // Hungria
            'cs_CZ' => 'CZ', // República Tcheca
            'el_GR' => 'GR', // Grécia
            'da_DK' => 'DK', // Dinamarca
            'fi_FI' => 'FI', // Finlândia
        ];

        $country = $localeToCountry[$ip] ?? "BR";

        $not_found = true;
        $offset = 0;
        foreach ($this->players as $item) {
            if (isset($item[$country])) {
                $item[$country] = $item[$country] + 1;
                $this->players->offsetSet($offset, $item);
                $not_found = false;
            }
            $offset++;
        }

        if ($not_found) {
            $this->players->push([$country => 1]);
        }
    }

    public function sendRequest() {
        $data = $this->players->current();
        $this->players->next();
        if ($data) {
            API::request([key($data), current($data)], key($data), $this->chart_token);
            $this->players->next();

            if (!$this->players->valid()) {
                $this->save();
                $this->players->rewind();
            }
        }
    }
}

class PlayerCountryTask extends Task {
    private $primary_request = false;

    public function __construct(private PlayerCountry $owner) {
    }

    public function onRun(int $currentTick) {
        if ($this->primary_request) {
            $this->owner->sendRequest();
        } else {
            $this->primary_request = true;
        }
    }
}
