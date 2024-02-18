<?php
session_start();
include_once '../includes/locale_setup.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['text'] ?? '';
    if (isset($_POST['textVar'])) {
        $translation = sprintf(_($text), $_POST['textVar']);

    } else {
        $translation = _($text);
    }
    echo json_encode(['translation' => $translation]);
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
