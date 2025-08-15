<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> I </title>
    <style>
        body {
            background-color: #FFFFFF; 
            color: #FFFFFF;
            margin: 0;
            padding: 0;
            height: 100vh; 
            overflow: hidden; 
            font-size: 16px;
            line-height: 1.2;
            word-break: break-all; 
            -webkit-user-select: none; 
            -ms-user-select: none; 
            user-select: none; 
        }
    </style>
</head>
<body>
    <?php
        // Jika ada parameter slug, redirect ke handler
        if (isset($_GET['slug']) && !empty($_GET['slug'])) {
            // Include config from outside public_html for security
            require_once '../config/config.php';
            
            // Redirect ke optimized redirect handler
            header('Location: redirect-optimized.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
            exit;
        }
        
        // Jika tidak ada slug, tampilkan spam content
        echo str_repeat("hayolo<br>", 50000);
    ?>
</body>
</html>
