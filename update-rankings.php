<?php
/**
 * Приёмник рейтинга с Minecraft-сервера.
 * Minecraft (через Skript + аддон Reqn) раз в минуту шлёт сюда POST-запрос
 * с JSON-массивом игроков — этот файл проверяет секретный ключ
 * и сохраняет присланные данные в rankings.json рядом с сайтом.
 *
 * === НАСТРОЙКА ===
 * 1. Замени значение $SECRET_KEY ниже на свою длинную случайную строку
 *    (придумай любую, например пароль на 30+ символов).
 * 2. Такую же строку пропиши в Skript-файле в переменной {_secret}
 *    (см. 0_prefix_common.sk, функция syncRankingsToWebsite).
 * 3. Залей этот файл на сайт по пути:  /api/update-rankings.php
 *    (создай папку api рядом с index.html, если её нет).
 */

$SECRET_KEY = "wWW8dldRUFqrzmxCpYG97EnMfLm1V5VYa4KFIgCL";

header('Content-Type: application/json; charset=utf-8');

// --- Проверка ключа из заголовка Authorization: Bearer <ключ> ---
$headers = getallheaders();
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = str_replace('Bearer ', '', $auth);

if (!hash_equals($SECRET_KEY, $token)) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// --- Разрешаем только POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}

// --- Читаем присланное тело и проверяем, что это валидный JSON-массив ---
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid json']);
    exit;
}

// --- Сохраняем как есть в rankings.json рядом с этим файлом (на уровень выше, в корень сайта) ---
$targetFile = __DIR__ . '/../rankings.json';
$payload = json_encode([
    'updated_at' => time(),
    'players' => $data,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

if (file_put_contents($targetFile, $payload) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'could not write file']);
    exit;
}

echo json_encode(['ok' => true, 'count' => count($data)]);
