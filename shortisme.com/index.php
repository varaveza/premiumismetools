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
        // Jika ada parameter slug, langsung redirect
        if (isset($_GET['slug']) && !empty($_GET['slug'])) {
            // Include config from outside public_html for security
            require_once '../config/config.php';
            
            $slug = $_GET['slug'];
            $pdo = getDBConnection();
            
            if ($pdo) {
                try {
                    // Get link data with prepared statement for security
                    $stmt = $pdo->prepare("
                        SELECT id, slug, original_url, clicks 
                        FROM shortlinks 
                        WHERE slug = ?
                    ");
                    $stmt->execute([$slug]);
                    $link = $stmt->fetch();
                    
                    if ($link) {
                        // Increment click count using atomic update
                        $stmt = $pdo->prepare("
                            UPDATE shortlinks 
                            SET clicks = clicks + 1, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$link['id']]);
                        
                        // Redirect to original URL
                        header("Location: " . $link['original_url']);
                        exit;
                    } else {
                        // Slug tidak ditemukan, tampilkan spam content
                        echo str_repeat("hayolo<br>", 50000);
                    }
                } catch (PDOException $e) {
                    // Error database, tampilkan spam content
                    echo str_repeat("hayolo<br>", 50000);
                }
            } else {
                // Koneksi database gagal, tampilkan spam content
                echo str_repeat("hayolo<br>", 50000);
            }
        } else {
            // Jika tidak ada slug, tampilkan spam content
            echo str_repeat("hayolo<br>", 50000);
        }
    ?>
</body>
</html>
