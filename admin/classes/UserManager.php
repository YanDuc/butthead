<?php
    if(!isset($_SESSION)) 
    { 
        session_start(); 
    } 
require_once __DIR__ . '/Logger.php';
include_once(__DIR__ . '/../includes/locale_setup.php');

class UserManager
{
    private $parentDirectory;
    private $usersJsonFilePath;
    private $usersArray;

    public function __construct()
    {
        $parentDirectory = realpath($_SERVER['DOCUMENT_ROOT'] . '/..');
        $this->usersJsonFilePath = $parentDirectory . '/private/users.json';

        if (file_exists($this->usersJsonFilePath)) {
            $this->usersArray = json_decode(file_get_contents($this->usersJsonFilePath), true);
            if (!is_array($this->usersArray) || empty($this->usersArray)) {
                $this->addDefaultUser();
            }
        } else {
            // create folder
            mkdir(dirname($this->usersJsonFilePath), 0777, true);
            $this->addDefaultUser();
            $this->usersArray = json_decode(file_get_contents($this->usersJsonFilePath), true);
        }
    }

    private function addDefaultUser()
    {
        $content = array(
            "email" => "admin@admin.fr",
            "password" => password_hash("admin", PASSWORD_BCRYPT),
            "admin" => true
        );
        $content = json_encode([$content], JSON_PRETTY_PRINT);
        file_put_contents($this->usersJsonFilePath, $content);
    }

    public function login($email, $password)
    {
        foreach ($this->usersArray as $user) {
            if ($user['email'] === $email) {
                $checkPass = password_verify($password, $user['password']);
                if ($checkPass) {
                    $_SESSION['loggedIn'] = [
                        'email' => $email,
                        'admin' => isset($user['admin']) ? $user['admin'] : false
                    ];
                    if (isset($user['lang'])) {
                        $_SESSION['loggedIn']['lang'] = $user['lang'];
                    }

                    // return associative array
                    return ['success' => true];
                }
            }
        }

        // throw an exception with the error message
        throw new Exception('Invalid email or password');
    }

    private function findUserByEmail($email)
    {
        foreach ($this->usersArray as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }
        return null;
    }

    public function logout()
    {
        $_SESSION['loggedIn'] = false;
        session_destroy();

        // return associative array
        return ['success' => true];
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'];
    }

    public function changePassword($password)
    {
        try {
            // get content of users.json
            $usersJsonArray = file_get_contents($this->usersJsonFilePath);
            $this->usersArray = json_decode($usersJsonArray, true);

            foreach ($this->usersArray as $key => $user) {
                if ($user['email'] === $_SESSION['loggedIn']['email']) {
                    $this->usersArray[$key]['password'] = password_hash($password, PASSWORD_BCRYPT);
                }
            }

            // update users.json
            $usersJson = json_encode(array_values($this->usersArray), JSON_PRETTY_PRINT);
            file_put_contents($this->usersJsonFilePath, $usersJson);
            return ['success' => true, 'message' => 'Password changed successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Change the role of a user.
     *
     * @param string $email The email of the user.
     * @param bool $admin Whether the user should have admin privileges.
     * @throws Exception If there is an error updating the users.json file.
     * @return array An associative array with the keys 'success' and 'message'. If the role is changed successfully, 'success' will be true and 'message' will be 'Role changed successfully'. If there is an error, 'success' will be false and 'message' will contain the error message.
     */
    public function changeRole($email, $admin)
    {
        $admin = is_bool($admin) ? $admin : ($admin === 'true' ? true : false);
        
        // get content of users.json
        $usersJsonArray = file_get_contents($this->usersJsonFilePath);
        $this->usersArray = json_decode($usersJsonArray, true);

        // check if last admin
        if ($this->isLastAdmin() && $admin === false) {
            throw new Exception(_("Cannot remove last admin"));
        }

        foreach ($this->usersArray as $key => $user) {
            if ($user['email'] === $email) {
                $this->usersArray[$key]['admin'] = $admin;
            }
        }

        try {
            $usersJson = json_encode(array_values($this->usersArray), JSON_PRETTY_PRINT);
            file_put_contents($this->usersJsonFilePath, $usersJson);
            return ['success' => true, 'message' => 'Role changed successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function isLastAdmin()
    {
        // count number of admins
        $adminsCount = 0;
        foreach ($this->usersArray as $user) {
            if ($user['admin']) {
                $adminsCount++;
            }
        }
        if ($adminsCount === 1) {
            return true;
        }
    }

    public function addUser($email)
    {
        if ($this->findUserByEmail($email)) {
            throw new Exception("User already exists");
        }
        try {
            $firstUser = sizeof($this->usersArray) === 1 && $this->usersArray[0]['email'] === 'admin@admin.fr';
            $admin = $firstUser ? true : false;
            $password = $this->generatePassword();

            $this->usersArray[] = ['email' => $email, 'password' => password_hash($password, PASSWORD_BCRYPT), 'admin' => $admin];
            $usersJson = json_encode(array_values($this->usersArray), JSON_PRETTY_PRINT);
            file_put_contents($this->usersJsonFilePath, $usersJson);

            // delete default user
            if ($this->findUserByEmail('admin@admin.fr')) {
                $this->deleteUser('admin@admin.fr');
            }

            // change session if first user
            if ($firstUser) {
                $_SESSION['loggedIn'] = ['email' => $email, 'admin' => $admin];
            }
            
            return ['success' => true, 'password' => $password];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Generate a random password between 10 and 12 characters with letters, numbers, and special characters.
     *
     * @return string
     */
    private function generatePassword()
    {
        // between 10 and 12 characters with letters numbers and special characters
        $length = rand(10, 12);
        $password = '';
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*()_+-=';
        for ($i = 0; $i < $length; $i++) {
            if ($i % 4 === 0 && $i > 0) {
                $password .= $specialChars[rand(0, strlen($specialChars) - 1)];
            } elseif ($i % 3 === 0 && $i > 0) {
                $password .= $numbers[rand(0, strlen($numbers) - 1)];
            } else {
                $password .= $chars[rand(0, strlen($chars) - 1)];
            }
        }
        return $password;
    }

    public function deleteUser($email)
    {
        $user = $this->findUserByEmail($email);
        if (!$user) {
            throw new Exception(_("User not found"));
        }
        if ($this->isLastAdmin() && $user['admin']) {
            throw new Exception(_("Cannot remove last admin"));
        }
        try {
            $this->usersArray = array_filter($this->usersArray, function ($user) use ($email) {
                return $user['email'] !== $email;
            });
            $usersJson = json_encode(array_values($this->usersArray), JSON_PRETTY_PRINT);
            file_put_contents($this->usersJsonFilePath, $usersJson);
            return ['success' => true];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getUsersArray()
    {
        return $this->usersArray;
    }
}
