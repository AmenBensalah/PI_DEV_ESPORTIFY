<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    ini_set('default_charset', 'UTF-8');
    date_default_timezone_set('Europe/Paris');
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
