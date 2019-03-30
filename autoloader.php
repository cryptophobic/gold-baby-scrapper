<?php

$paths = [__DIR__, __DIR__.'/Utils'];

spl_autoload_register(function ($className) use ($paths) {
    foreach ($paths as $path)
    {
        if (file_exists($className . '.php'))
        {
            include $className . '.php';
            break;
        }
    }
});
