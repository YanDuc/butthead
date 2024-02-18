<?php
    // Check if the language is set in the session
    if (isset($_SESSION['loggedIn']['lang'])) {
        $lang = $_SESSION['loggedIn']['lang'];
    } else {
        // Get the language from the browser
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        // Check if the language is supported
        if (!in_array($lang, ['fr', 'en'])) {
            $lang = 'en_US';
        } else {
            switch ($lang) {
                case 'fr':
                    $lang = 'fr_FR';
                    break;
                case 'en':
                    $lang = 'en_US';
                    break;
            }
        }
    }
    $domain = substr($lang, 0, 2);

    // Array of possible locale variations
    $localeVariations = [
        $lang,
        $lang . '.UTF-8',
        $lang . '.UTF8',
        // Add more variations if needed
    ];

    // Try each variation until one works
    $success = false;
    foreach ($localeVariations as $variation) {
        $success = setlocale(LC_ALL, $variation);
        if ($success !== false) {
            break;
        }
    }

    // If no variation worked, set the default locale
    if (!$success) {
        setlocale(LC_ALL, '');
    }

    // Set the environment variable
    putenv('LANGUAGE=' . $lang);

    // Set the domain and bind text domain
    bindtextdomain($domain, realpath('./') . DIRECTORY_SEPARATOR . 'locale');
    textdomain($domain);
?>