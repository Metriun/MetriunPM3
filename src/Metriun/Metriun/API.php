<?php

declare(strict_types=1);

namespace Metriun\Metriun;

use pocketmine\Server;
use pocketmine\utils\TextFormat;

use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;
use function json_decode;
use function json_encode;
use function time;

class API {
	const BASE_URL = "https://api.metriun.com/";

	public static function request(array $data, $change = false, $token = ""): array {
		$curl = curl_init();

		$data = [
			"time" => time(),
			"change" => $change,
			"data" => $data
		];

		curl_setopt_array($curl, [
			CURLOPT_URL => self::BASE_URL . "v1/send",
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "PUT",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer " . $token,
				"Content-Type: application/json"
			],
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			// Enviando o erro.
			if (isset($err["message"]) || $err["status"] !== 200) {
				$message = isset($err["message"]) ? $err["message"] : $err["data"];
				Server::getInstance()->getLogger()->warning(TextFormat::YELLOW . $message);
			}

			return json_decode($err, true);
		}

		return json_decode($response, true);
	}
}
