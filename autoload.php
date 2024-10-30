<?php
// spl_autoload_register(function ($class) {
//     $prefixPhpSpreadsheet = 'PhpOffice\\PhpSpreadsheet\\';
//     $base_dirPhpSpreadsheet = __DIR__ . '/phpspreadsheet/src/PhpSpreadsheet/';

//     $prefixPsr = 'Psr\\SimpleCache\\';
//     $base_dirPsr = __DIR__ . './/vendor/Psr/SimpleCache/src/'; // Đường dẫn đến thư mục PSR SimpleCache

//     // Tải các lớp của PhpSpreadsheet
//     if (strncmp($prefixPhpSpreadsheet, $class, strlen($prefixPhpSpreadsheet)) === 0) {
//         $relative_class = substr($class, strlen($prefixPhpSpreadsheet));
//         $file = $base_dirPhpSpreadsheet . str_replace('\\', '/', $relative_class) . '.php';
//         if (file_exists($file)) {
//             require $file;
//         }
//         return;
//     }

//     // Tải các lớp của Psr
//     if (strncmp($prefixPsr, $class, strlen($prefixPsr)) === 0) {
//         $relative_class = substr($class, strlen($prefixPsr));
//         $file = $base_dirPsr . str_replace('\\', '/', $relative_class) . '.php';
//         if (file_exists($file)) {
//             require $file;
//         }
//         return;
//     }
// });

// Tải tất cả các lớp của PhpSpreadsheet
spl_autoload_register(function ($class) {
    $prefixPhpSpreadsheet = 'PhpOffice\\PhpSpreadsheet\\';
    $base_dirPhpSpreadsheet = __DIR__ . '/vendor/phpspreadsheet/src/PhpSpreadsheet/';

    $prefixPsr = 'Psr\\';
    $base_dirPsr = __DIR__ . '/vendor/Psr/'; // Đường dẫn đến thư mục PSR

    // Tải các lớp của PhpSpreadsheet
    if (strncmp($prefixPhpSpreadsheet, $class, strlen($prefixPhpSpreadsheet)) === 0) {
        $relative_class = substr($class, strlen($prefixPhpSpreadsheet));
        $file = $base_dirPhpSpreadsheet . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }

    // Tải tất cả các lớp của Psr
    if (strncmp($prefixPsr, $class, strlen($prefixPsr)) === 0) {
        $relative_class = substr($class, strlen($prefixPsr));
        $file = $base_dirPsr . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }
});
?>

