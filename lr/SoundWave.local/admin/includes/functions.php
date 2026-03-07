<?php
// Функция для логирования ошибок
function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $log .= ': ' . print_r($data, true);
    }
    error_log($log);
}