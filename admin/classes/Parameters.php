<?php
    if(!isset($_SESSION)) 
    { 
        session_start(); 
    } 
class Parameters
{
    // private $usersJsonFilePath = __DIR__ . '../../../../private/users.json';
    private $usersJsonFilePath;

    public function __construct() {
        $parentDirectory = realpath($_SERVER['DOCUMENT_ROOT'] . '/..');
        $this->usersJsonFilePath = $parentDirectory . '/private/users.json';
    }

    public function changeLocale($locale)
    {
        if ($locale === 'fr_FR') {
            $_SESSION['loggedIn']['lang'] = 'fr_FR';

        } else {
            $_SESSION['loggedIn']['lang'] = 'en_US';
        }

        // get content of users.json
        $usersJsonArray = file_get_contents($this->usersJsonFilePath);
        $usersArray = json_decode($usersJsonArray, true);

        foreach ($usersArray as $key => $user) {
            if ($user['email'] === $_SESSION['loggedIn']['email']) {
                $usersArray[$key]['lang'] = $_SESSION['loggedIn']['lang'];
            }
        }

        // update users.json
        try {
            $usersJson = json_encode(array_values($usersArray), JSON_PRETTY_PRINT);
            file_put_contents($this->usersJsonFilePath, $usersJson);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}