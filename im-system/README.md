# IM 即时通讯系统

基于 PHP 8.2 + MySQL 5.7 + Redis 的即时通讯系统，支持用户功能、私聊、群聊、管理员后台和 WebSocket 实时通信。

## 功能特性

- **用户系统**：注册、登录、个人资料管理、头像上传
- **好友功能**：添加好友、好友请求处理、好友列表管理
- **私聊功能**：发送私信、消息历史、未读消息提醒
- **群聊功能**：创建群组、加入/退出群组、设置管理员、群消息
- **实时通信**：基于 WebSocket 的消息推送、用户状态同步、正在输入提示
- **管理员后台**：用户管理、群组管理、消息管理、系统设置

## 技术栈

- **后端**：PHP 8.2 (原生开发)
- **数据库**：MySQL 5.7
- **缓存**：Redis
- **实时通信**：Ratchet WebSocket
- **认证**：JWT Token
- **依赖管理**：Composer

## 环境要求

- PHP 8.2+
- MySQL 5.7+
- Redis
- Composer

## 安装步骤

### 1. 克隆项目

```bash
git clone <repository-url>
cd im-system
```

### 2. 安装依赖

```bash
composer install
```

### 3. 配置数据库

创建 MySQL 数据库并导入 schema：

```bash
mysql -u root -p < database/schema.sql
```

### 4. 修改配置文件

编辑配置文件，修改数据库和 Redis 连接信息：

**config/database.php**
```php
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'im_system',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
];
```

**config/redis.php**
```php
return [
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => null,
    'database' => 0,
];
```

**config/app.php**
```php
return [
    'jwt_secret' => 'your-secret-key-change-in-production',
    // ...
];
```

### 5. 启动服务

**启动 HTTP API 服务器**
```bash
php -S localhost:8080 -t public
```

**启动 WebSocket 服务器**
```bash
php public/ws_server.php
```

## API 文档

### 认证接口

| 方法 | 路径 | 描述 | 认证 |
|------|------|------|------|
| POST | /api/auth/register | 用户注册 | 否 |
| POST | /api/auth/login | 用户登录 | 否 |
| POST | /api/auth/logout | 用户登出 | 是 |
| POST | /api/auth/refresh | 刷新 Token | 否 |

### 用户接口

| 方法 | 路径 | 描述 | 认证 |
|------|------|------|------|
| GET | /api/user/profile | 获取用户信息 | 是 |
| PUT | /api/user/profile | 更新用户信息 | 是 |
| PUT | /api/user/password | 修改密码 | 是 |
| POST | /api/user/avatar | 上传头像 | 是 |
| GET | /api/user/search | 搜索用户 | 是 |

### 好友接口

| 方法 | 路径 | 描述 | 认证 |
|------|------|------|------|
| GET | /api/friends | 获取好友列表 | 是 |
| GET | /api/friends/pending | 获取待处理请求 | 是 |
| POST | /api/friends/request | 发送好友请求 | 是 |
| PUT | /api/friends/request/{id} | 处理好友请求 | 是 |
| DELETE | /api/friends/{friendId} | 删除好友 | 是 |

### 私信接口

| 方法 | 路径 | 描述 | 认证 |
|------|------|------|------|
| POST | /api/message/private | 发送私信 | 是 |
| GET | /api/message/private/history/{userId} | 获取聊天历史 | 是 |
| PUT | /api/message/private/read/{userId} | 标记消息已读 | 是 |
| GET | /api/message/private/unread | 获取未读数 | 是 |
| GET | /api/message/conversations | 获取会话列表 | 是 |

### 群聊接口

| 方法 | 路径 | 描述 | 认证 |
|------|------|------|------|
| GET | /api/group | 获取群组列表 | 是 |
| POST | /api/group | 创建群组 | 是 |
| GET | /api/group/{id} | 获取群组详情 | 是 |
| POST | /api/group/{id}/join | 加入群组 | 是 |
| POST | /api/group/{id}/leave | 退出群组 | 是 |
| PUT | /api/group/{id}/admin/{userId} | 设置管理员 | 是 |
| DELETE | /api/group/{id}/member/{userId} | 移除成员 | 是 |
| POST | /api/group/{id}/message | 发送群消息 | 是 |
| GET | /api/group/{id}/message | 获取群消息历史 | 是 |

### 管理员接口

| 方法 | 路径 | 描述 | 认证 |
|------|------|------|------|
| POST | /api/admin/login | 管理员登录 | 否 |
| GET | /api/admin/dashboard | 仪表盘统计 | 是 |
| GET | /api/admin/users | 用户列表 | 是 |
| GET | /api/admin/users/{id} | 用户详情 | 是 |
| PUT | /api/admin/users/{id} | 编辑用户 | 是 |
| DELETE | /api/admin/users/{id} | 删除用户 | 是 |
| PUT | /api/admin/users/{id}/status | 封禁/解封用户 | 是 |
| GET | /api/admin/groups | 群组列表 | 是 |
| DELETE | /api/admin/groups/{id} | 解散群组 | 是 |
| GET | /api/admin/messages | 消息列表 | 是 |
| DELETE | /api/admin/messages/{id} | 删除消息 | 是 |
| GET | /api/admin/settings | 系统设置 | 是 |
| PUT | /api/admin/settings | 更新设置 | 是 |

## API 使用示例

### 用户注册

```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "password123",
    "nickname": "Test User"
  }'
```

### 用户登录

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "password123"
  }'
```

### 发送私信

```bash
curl -X POST http://localhost:8080/api/message/private \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "to_user_id": 2,
    "content": "Hello, this is a test message",
    "message_type": 1
  }'
```

## WebSocket 使用

### 连接

```javascript
const ws = new WebSocket('ws://localhost:8080');

// 认证
ws.onopen = () => {
  ws.send(JSON.stringify({
    type: 'auth',
    token: 'YOUR_ACCESS_TOKEN'
  }));
};

// 接收消息
ws.onmessage = (event) => {
  const data = JSON.parse(event.data);
  console.log(data);
};
```

### 发送私聊消息

```javascript
ws.send(JSON.stringify({
  type: 'private_message',
  to_user_id: 2,
  content: 'Hello!',
  message_type: 1
}));
```

### 发送群消息

```javascript
ws.send(JSON.stringify({
  type: 'group_message',
  group_id: 1,
  content: 'Hello, group!',
  message_type: 1
}));
```

### 发送正在输入状态

```javascript
ws.send(JSON.stringify({
  type: 'typing',
  to_user_id: 2
}));
```

### 心跳

```javascript
setInterval(() => {
  ws.send(JSON.stringify({ type: 'heartbeat' }));
}, 30000);
```

## 默认管理员

- 用户名：admin
- 密码：admin123 (请在生产环境修改)

## 响应格式

### 成功响应

```json
{
  "code": 200,
  "message": "success",
  "data": { ... }
}
```

### 错误响应

```json
{
  "code": 400,
  "message": "错误描述",
  "data": {
    "error_code": "ERROR_CODE"
  }
}
```

### 分页响应

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [...],
    "pagination": {
      "total": 100,
      "page": 1,
      "page_size": 20,
      "total_pages": 5
    }
  }
}
```

## 项目结构

```
im-system/
├── app/
│   ├── Controllers/     # 控制器
│   ├── Models/          # 数据模型
│   ├── Services/        # 业务服务
│   ├── Middleware/      # 中间件
│   └── Utils/           # 工具类
├── config/              # 配置文件
├── database/            # 数据库脚本
├── public/              # 公共入口
│   ├── index.php        # HTTP 入口
│   └── ws_server.php    # WebSocket 入口
├── routes/              # 路由配置
├── vendor/              # Composer 依赖
└── composer.json
```

## 许可证

MIT License
