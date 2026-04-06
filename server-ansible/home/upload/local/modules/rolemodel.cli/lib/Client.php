<?
namespace RoleModel\Webhook;

use GuzzleHttp\Client as GuzzleClient;
use Bitrix\Main\Config\Configuration;

/*
Смена архитектуры: 
Мы перешли с модели Push (когда Битрикс сам ломится в React через Guzzle) 
на модель Pull (когда React забирает данные через очередь bx:webhook-pop).
Лицензионный барьер: 
Этот класс использует Bitrix\Main\Config\Configuration и требует стабильной работы ядра. 
Как мы видели, ядро Битрикса при HTTP-запросах часто пытается «умереть» из-за лицензии, 
что сделало бы отправку через Guzzle нестабильной.
Очередь надежнее: 
Текущая схема с БД (PostgreSQL) гарантирует, что хук не потеряется, если React-приложение или Vite-сервер были временно выключены. 
GuzzleClient же просто выдал бы ошибку соединения и забыл про событие.
*/

class Client {
    public static function send(string $eventType, array $payload) {
        $config = Configuration::getInstance()->get('rolemodel.webhook');
        
        $client = new GuzzleClient([
            'base_uri' => $config['frontend_url'],
            'timeout'  => $config['timeout'] ?? 2.0,
        ]);

        try {
            $client->post('', [
                'json' => [
                    'event' => $eventType,
                    'data' => $payload,
                    'ts' => time()
                ]
            ]);
        } catch (\Exception $e) {
            // В CLI режиме можно писать в лог
            \add_to_log("Webhook Error: " . $e->getMessage());
        }
    }
}
