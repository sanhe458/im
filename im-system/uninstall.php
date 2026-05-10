<?php
define('INSTALL_LOCK_FILE', __DIR__ . '/install.lock');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (file_exists(INSTALL_LOCK_FILE)) {
        unlink(INSTALL_LOCK_FILE);
    }
    
    if (is_dir(__DIR__ . '/config')) {
        array_map('unlink', glob(__DIR__ . '/config/*.php'));
    }
    
    echo json_encode(['success' => true, 'message' => '重置成功']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>卸载/重装 - IM系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #c92a2a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .icon { font-size: 60px; margin-bottom: 20px; }
        h1 { color: #333; margin-bottom: 15px; font-size: 24px; }
        p { color: #666; margin-bottom: 30px; line-height: 1.8; }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #ff6b6b 0%, #c92a2a 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            box-shadow: 0 10px 30px rgba(108, 117, 125, 0.4);
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
            text-align: left;
        }
        .warning strong { display: block; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⚠️</div>
        <h1>卸载/重装系统</h1>
        
        <div class="warning">
            <strong>⚠️ 警告</strong>
            此操作将删除安装锁文件和配置文件。<br>
            数据库中的数据不会被删除。<br>
            删除后，您可以重新运行 install.php 进行全新安装。
        </div>
        
        <p>
            <button class="btn" onclick="resetSystem()">确认重置</button>
            <a href="public/index.php" class="btn btn-secondary">返回首页</a>
        </p>
    </div>
    
    <script>
        function resetSystem() {
            if (!confirm('确定要重置系统吗？此操作不可撤销！')) {
                return;
            }
            
            fetch('uninstall.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('重置成功！现在可以重新安装。');
                    window.location.href = 'install.php';
                }
            });
        }
    </script>
</body>
</html>
