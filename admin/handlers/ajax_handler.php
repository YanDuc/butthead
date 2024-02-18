<?php
require_once __DIR__ . '/../classes/Logger.php';
try {
    // Get the class and method names from the AJAX request
    $className = $_POST['class'];
    $methodName = $_POST['method'];
    $params = [];

    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['class', 'method', 'contructor'])) {
            $params[$key] = $value;
        }
    }
    foreach ($_FILES as $key => $value) {
        if (!in_array($key, ['class', 'method', 'contructor'])) {
            $params[$key] = $value;
        }
    }
    
    // Include the class file
    include_once('../classes/' . $className . '.php');

    if (isset($_POST['contructor']) && $_POST['contructor']) {
        // split array into separate elements
        $classInstance = new $className($_POST['contructor']);
    } else {
        $classInstance = new $className();
    }
    
    // Call the method dynamically with additional parameters
    $response = call_user_func_array([$classInstance, $methodName], $params);
    
    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} catch(Exception $e) {
    http_response_code(500); // Set the HTTP response status code to 500 (Internal Server Error)
    echo $e->getMessage();
}
?>