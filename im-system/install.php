<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('INSTALL_LOCK_FILE', __DIR__ . '/install.lock');
define('CONFIG_DIR', __DIR__ . '/config');
define('DATABASE_SCHEMA_FILE', __DIR__ . '/database/schema.sql');

session_start();

function isInstalled() {
    return file_exists(INSTALL_LOCK_FILE);
}

function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function validatePassword($password) {
    if (strlen($password) < 6) {
        return '密码长度至少6位';
    }
    return true;
}

function testDatabaseConnection($host, $port, $username, $password, $database) {
    try {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => '数据库连接失败: ' . $e->getMessage()];
    }
}

function executeSchema($pdo, $database, $adminUsername, $adminPassword) {
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$database}`");
        
        $schema = file_get_contents(DATABASE_SCHEMA_FILE);
        
        $schema = preg_replace('/CREATE DATABASE.*?;/', '', $schema);
        $schema = preg_replace('/USE.*?;/', '', $schema);
        
        $adminPasswordHash = password_hash($adminPassword, PASSWORD_BCRYPT);
        $schema = preg_replace(
            "/INSERT INTO `admins`.*?VALUES\s*\([^)]+\);/s",
            "INSERT INTO `admins` (`username`, `password`, `role`) VALUES ('{$adminUsername}', '{$adminPasswordHash}', 2);",
            $schema
        );
        
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => '数据库初始化失败: ' . $e->getMessage()];
    }
}

function createConfigFiles($dbConfig, $redisConfig, $appConfig) {
    try {
        if (!is_dir(CONFIG_DIR)) {
            mkdir(CONFIG_DIR, 0755, true);
        }
        
        $dbConfigContent = "<?php\nreturn [\n";
        $dbConfigContent .= "    'host' => '{$dbConfig['host']}',\n";
        $dbConfigContent .= "    'port' => {$dbConfig['port']},\n";
        $dbConfigContent .= "    'database' => '{$dbConfig['database']}',\n";
        $dbConfigContent .= "    'username' => '{$dbConfig['username']}',\n";
        $dbConfigContent .= "    'password' => '{$dbConfig['password']}',\n";
        $dbConfigContent .= "    'charset' => 'utf8mb4',\n";
        $dbConfigContent .= "    'collation' => 'utf8mb4_unicode_ci',\n";
        $dbConfigContent .= "    'prefix' => '',\n";
        $dbConfigContent .= "];\n";
        file_put_contents(CONFIG_DIR . '/database.php', $dbConfigContent);
        
        $redisConfigContent = "<?php\nreturn [\n";
        $redisConfigContent .= "    'host' => '{$redisConfig['host']}',\n";
        $redisConfigContent .= "    'port' => {$redisConfig['port']},\n";
        $redisConfigContent .= "    'password' => " . (empty($redisConfig['password']) ? 'null' : "'{$redisConfig['password']}'") . ",\n";
        $redisConfigContent .= "    'database' => 0,\n";
        $redisConfigContent .= "];\n";
        file_put_contents(CONFIG_DIR . '/redis.php', $redisConfigContent);
        
        $jwtSecret = generateRandomString(64);
        $appConfigContent = "<?php\nreturn [\n";
        $appConfigContent .= "    'name' => 'IM System',\n";
        $appConfigContent .= "    'debug' => false,\n";
        $appConfigContent .= "    'timezone' => 'Asia/Shanghai',\n";
        $appConfigContent .= "    'jwt_secret' => '{$jwtSecret}',\n";
        $appConfigContent .= "    'jwt_expire' => 604800,\n";
        $appConfigContent .= "    'refresh_token_expire' => 2592000,\n";
        $appConfigContent .= "    'websocket_port' => 8080,\n";
        $appConfigContent .= "];\n";
        file_put_contents(CONFIG_DIR . '/app.php', $appConfigContent);
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => '配置文件创建失败: ' . $e->getMessage()];
    }
}

function createInstallLock() {
    $lockData = [
        'installed_at' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    ];
    
    return file_put_contents(INSTALL_LOCK_FILE, json_encode($lockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isInstalled()) {
    header('Content-Type: application/json');
    
    $dbConfig = [
        'host' => trim($_POST['db_host'] ?? ''),
        'port' => (int)($_POST['db_port'] ?? 3306),
        'database' => trim($_POST['db_name'] ?? ''),
        'username' => trim($_POST['db_username'] ?? ''),
        'password' => $_POST['db_password'] ?? '',
    ];
    
    $redisConfig = [
        'host' => trim($_POST['redis_host'] ?? '127.0.0.1'),
        'port' => (int)($_POST['redis_port'] ?? 6379),
        'password' => $_POST['redis_password'] ?? '',
    ];
    
    $adminUsername = trim($_POST['admin_username'] ?? '');
    $adminPassword = $_POST['admin_password'] ?? '';
    
    $errors = [];
    
    if (empty($dbConfig['host'])) $errors[] = '数据库主机不能为空';
    if (empty($dbConfig['database'])) $errors[] = '数据库名称不能为空';
    if (empty($dbConfig['username'])) $errors[] = '数据库用户名不能为空';
    if (empty($adminUsername)) $errors[] = '管理员用户名不能为空';
    
    $passwordValidation = validatePassword($adminPassword);
    if ($passwordValidation !== true) {
        $errors[] = $passwordValidation;
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
    
    $connectionTest = testDatabaseConnection(
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['database']
    );
    
    if (!$connectionTest['success']) {
        echo json_encode(['success' => false, 'errors' => [$connectionTest['error']]]);
        exit;
    }
    
    $pdo = $connectionTest['pdo'];
    
    $schemaResult = executeSchema($pdo, $dbConfig['database'], $adminUsername, $adminPassword);
    if (!$schemaResult['success']) {
        echo json_encode(['success' => false, 'errors' => [$schemaResult['error']]]);
        exit;
    }
    
    $configResult = createConfigFiles($dbConfig, $redisConfig, []);
    if (!$configResult['success']) {
        echo json_encode(['success' => false, 'errors' => [$configResult['error']]]);
        exit;
    }
    
    createInstallLock();
    
    echo json_encode([
        'success' => true,
        'message' => '安装成功！',
        'redirect' => 'index.php'
    ]);
    exit;
}

if (isInstalled()): ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统已安装 - IM系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 60px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .icon { font-size: 80px; margin-bottom: 30px; }
        h1 { color: #333; margin-bottom: 20px; font-size: 28px; }
        p { color: #666; margin-bottom: 30px; line-height: 1.8; }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✓</div>
        <h1>系统已安装</h1>
        <p>IM 即时通讯系统已经完成安装。<br>如需重新安装，请先删除根目录下的 <strong>install.lock</strong> 文件。</p>
        <a href="public/index.php" class="btn">访问系统</a>
    </div>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - IM系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        .header h1 { font-size: 36px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 18px; }
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #999;
            font-size: 13px;
        }
        .btn-install {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .btn-install:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .error-box {
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #c00;
        }
        .success-box {
            background: #efe;
            border: 1px solid #cfc;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #060;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 IM系统安装向导</h1>
            <p>配置您的即时通讯系统</p>
        </div>
        
        <div class="card">
            <div id="error-box" class="error-box" style="display: none;"></div>
            
            <form id="install-form">
                <div class="section">
                    <div class="section-title">📦 数据库配置</div>
                    
                    <div class="form-group">
                        <label>数据库主机</label>
                        <input type="text" name="db_host" value="127.0.0.1" required>
                        <small>通常为 localhost 或 127.0.0.1</small>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库端口</label>
                        <input type="number" name="db_port" value="3306" required>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库名称</label>
                        <input type="text" name="db_name" value="im_system" required>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库用户名</label>
                        <input type="text" name="db_username" required>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库密码</label>
                        <input type="password" name="db_password">
                        <small>如果数据库没有密码，请留空</small>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">📦 Redis 配置</div>
                    
                    <div class="form-group">
                        <label>Redis 主机</label>
                        <input type="text" name="redis_host" value="127.0.0.1">
                    </div>
                    
                    <div class="form-group">
                        <label>Redis 端口</label>
                        <input type="number" name="redis_port" value="6379">
                    </div>
                    
                    <div class="form-group">
                        <label>Redis 密码</label>
                        <input type="password" name="redis_password">
                        <small>如果 Redis 没有密码，请留空</small>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">👤 管理员账户</div>
                    
                    <div class="form-group">
                        <label>管理员用户名</label>
                        <input type="text" name="admin_username" required>
                    </div>
                    
                    <div class="form-group">
                        <label>管理员密码</label>
                        <input type="password" name="admin_password" required>
                        <small>密码长度至少6位</small>
                    </div>
                </div>
                
                <button type="submit" class="btn-install" id="submit-btn">
                    开始安装
                </button>
            </form>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>正在安装，请稍候...</p>
            </div>
            
            <div id="success-box" class="success-box" style="display: none; text-align: center;">
                <h3 style="margin-bottom: 15px;">🎉 安装成功！</h3>
                <p>正在跳转...</p>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('install-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = document.getElementById('submit-btn');
            const loading = document.getElementById('loading');
            const errorBox = document.getElementById('error-box');
            const successBox = document.getElementById('success-box');
            
            errorBox.style.display = 'none';
            form.style.display = 'none';
            loading.style.display = 'block';
            
            const formData = new FormData(form);
            
            fetch('install.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                
                if (data.success) {
                    successBox.style.display = 'block';
                    setTimeout(() => {
                        window.location.href = 'public/index.php';
                    }, 1500);
                } else {
                    errorBox.innerHTML = data.errors ? data.errors.join('<br>') : '安装失败';
                    errorBox.style.display = 'block';
                    form.style.display = 'block';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                errorBox.innerHTML = '网络错误，请重试';
                errorBox.style.display = 'block';
                form.style.display = 'block';
            });
        });
    </script>
</body>
</html>
<?php endif; ?>
