# 即时通讯系统 (IM System) 规格文档

## 一、项目概述

### 项目名称
IM即时通讯系统 (PHP-based Instant Messaging System)

### 项目目标
搭建一个功能完整的即时通讯系统，支持用户管理、私聊功能、群聊功能，并提供完善的API接口和管理员后台。

### 核心技术栈
- **后端框架**: PHP 8.2 (原生开发，简洁高效)
- **数据库**: MySQL 8.0+
- **缓存系统**: Redis
- **实时通信**: WebSocket (Ratchet库)
- **API风格**: RESTful API

---

## 二、系统架构

### 2.1 整体架构
```
┌─────────────────┐
│   客户端应用     │
│  (Web/App)      │
└────────┬────────┘
         │ HTTP/WebSocket
         ▼
┌─────────────────┐
│   API 网关层     │
│  (PHP Router)   │
└────────┬────────┘
         │
    ┌────┴────┐
    ▼         ▼
┌───────┐  ┌───────┐
│ MySQL │  │ Redis │
│ 存储层 │  │ 缓存层 │
└───────┘  └───────┘
```

### 2.2 数据库设计

#### 用户表 (users)
| 字段 | 类型 | 描述 |
|------|------|------|
| id | INT UNSIGNED | 主键，自增 |
| username | VARCHAR(50) | 用户名，唯一 |
| nickname | VARCHAR(100) | 昵称 |
| email | VARCHAR(100) | 邮箱，唯一 |
| password | VARCHAR(255) | 密码哈希 |
| avatar | VARCHAR(255) | 头像URL |
| status | TINYINT | 状态: 0禁用 1正常 2在线 |
| last_login_at | DATETIME | 最后登录时间 |
| created_at | DATETIME | 创建时间 |
| updated_at | DATETIME | 更新时间 |

#### 好友关系表 (friendships)
| 字段 | 类型 | 描述 |
|------|------|------|
| id | INT UNSIGNED | 主键 |
| user_id | INT UNSIGNED | 用户ID |
| friend_id | INT UNSIGNED | 好友ID |
| status | TINYINT | 状态: 0待确认 1已添加 2已拒绝 |
| created_at | DATETIME | 创建时间 |

#### 私聊消息表 (private_messages)
| 字段 | 类型 | 描述 |
|------|------|------|
| id | BIGINT UNSIGNED | 主键 |
| from_user_id | INT UNSIGNED | 发送者ID |
| to_user_id | INT UNSIGNED | 接收者ID |
| content | TEXT | 消息内容 |
| message_type | TINYINT | 类型: 1文本 2图片 3文件 |
| is_read | TINYINT | 已读标记 |
| created_at | DATETIME | 发送时间 |

#### 群组表 (groups)
| 字段 | 类型 | 描述 |
|------|------|------|
| id | INT UNSIGNED | 主键 |
| name | VARCHAR(100) | 群名称 |
| description | TEXT | 群描述 |
| avatar | VARCHAR(255) | 群头像 |
| owner_id | INT UNSIGNED | 群主ID |
| created_at | DATETIME | 创建时间 |

#### 群组成员表 (group_members)
| 字段 | 类型 | 描述 |
|------|------|------|
| id | INT UNSIGNED | 主键 |
| group_id | INT UNSIGNED | 群ID |
| user_id | INT UNSIGNED | 用户ID |
| role | TINYINT | 角色: 1成员 2管理员 3群主 |
| joined_at | DATETIME | 加入时间 |

#### 群聊消息表 (group_messages)
| 字段 | 类型 | 描述 |
|------|------|------|
| id | BIGINT UNSIGNED | 主键 |
| group_id | INT UNSIGNED | 群ID |
| user_id | INT UNSIGNED | 发送者ID |
| content | TEXT | 消息内容 |
| message_type | TINYINT | 类型: 1文本 2图片 3文件 |
| created_at | DATETIME | 发送时间 |

---

## 三、功能模块

### 3.1 用户模块

#### 用户注册
- **POST** `/api/auth/register`
- 请求参数: username, email, password, nickname
- 响应: 用户信息 + token
- 验证: 邮箱格式、密码强度、用户名唯一性

#### 用户登录
- **POST** `/api/auth/login`
- 请求参数: email/username, password
- 响应: 用户信息 + JWT token
- Redis记录登录状态和token

#### 用户登出
- **POST** `/api/auth/logout`
- 清除Redis中的登录状态

#### 获取用户信息
- **GET** `/api/user/profile`
- 认证: Bearer Token
- 响应: 用户详细信息

#### 更新用户信息
- **PUT** `/api/user/profile`
- 可更新: nickname, avatar, email

#### 修改密码
- **PUT** `/api/user/password`
- 需验证原密码

### 3.2 好友管理模块

#### 发送好友请求
- **POST** `/api/friends/request`
- 请求参数: friend_id
- 响应: 好友请求状态

#### 处理好友请求
- **PUT** `/api/friends/request/{id}`
- 请求参数: status (accept/reject)
- 响应: 处理结果

#### 获取好友列表
- **GET** `/api/friends`
- 响应: 好友列表（包含在线状态）

#### 删除好友
- **DELETE** `/api/friends/{friend_id}`
- 响应: 删除结果

### 3.3 私聊模块

#### 发送私信
- **POST** `/api/message/private`
- 请求参数: to_user_id, content, message_type
- 响应: 消息ID + 发送状态
- 通过WebSocket实时推送

#### 获取与某用户的历史消息
- **GET** `/api/message/private/history/{user_id}`
- 参数: limit, before_id (分页)
- 响应: 消息列表

#### 标记消息已读
- **PUT** `/api/message/private/read/{user_id}`
- 响应: 已读标记结果

#### 获取未读消息数
- **GET** `/api/message/private/unread`
- 响应: 未读消息统计

### 3.4 群聊模块

#### 创建群组
- **POST** `/api/group`
- 请求参数: name, description, avatar
- 响应: 群组信息

#### 获取群组列表
- **GET** `/api/group`
- 响应: 用户所在的群组列表

#### 获取群组详情
- **GET** `/api/group/{id}`
- 响应: 群组详细信息 + 成员列表

#### 加入群组
- **POST** `/api/group/{id}/join`
- 请求参数: invite_code (可选)
- 响应: 加入结果

#### 退出群组
- **POST** `/api/group/{id}/leave`
- 响应: 退出结果

#### 设置群管理员
- **PUT** `/api/group/{id}/admin/{user_id}`
- 权限: 仅群主
- 响应: 设置结果

#### 发送群消息
- **POST** `/api/group/{id}/message`
- 请求参数: content, message_type
- 响应: 消息ID
- 通过WebSocket实时推送

#### 获取群历史消息
- **GET** `/api/group/{id}/message`
- 参数: limit, before_id
- 响应: 消息列表

### 3.5 WebSocket实时通信

#### 连接认证
- `ws://domain:8080/ws`
- 连接时携带token进行认证

#### 消息类型
- `private_message` - 私聊消息
- `group_message` - 群聊消息
- `friend_request` - 好友请求通知
- `user_status` - 用户状态变更
- `typing` - 正在输入状态

#### 心跳机制
- 客户端每30秒发送心跳
- 服务端检测连接断开后更新用户状态

---

## 四、Redis缓存设计

### 4.1 会话管理
```
# 用户登录token
token:{token} -> user_id (TTL: 7天)

# 用户在线状态
online:user:{user_id} -> 1 (TTL: 60秒，自动续期)

# 用户WebSocket连接
ws:user:{user_id} -> connection_id
```

### 4.2 消息缓存
```
# 用户未读消息计数
unread:private:{user_id} -> count

# 群组未读消息计数
unread:group:{user_id}:{group_id} -> count

# 最近消息缓存
recent:private:{user_id}:{friend_id} -> [messages] (最新20条)

# 群组最新消息
recent:group:{group_id} -> [messages] (最新50条)
```

### 4.3 实时状态
```
# 用户正在输入
typing:private:{from}:{to} -> 1 (TTL: 5秒)

# 群组在线人数
online:group:{group_id} -> [user_ids]
```

---

## 五、管理员后台

### 5.1 管理员登录
- **POST** `/api/admin/login`
- 请求参数: username, password
- 响应: 管理员token

### 5.2 仪表盘
- **GET** `/api/admin/dashboard`
- 统计: 用户总数、在线人数、今日消息数、群组数
- 响应: 统计数据 + 趋势图数据

### 5.3 用户管理
- **GET** `/api/admin/users** - 用户列表（分页、搜索）
- **GET** `/api/admin/users/{id}** - 用户详情
- **PUT** `/api/admin/users/{id}** - 编辑用户
- **DELETE** `/api/admin/users/{id}** - 删除用户
- **PUT** `/api/admin/users/{id}/status** - 封禁/解封用户

### 5.4 群组管理
- **GET** `/api/admin/groups** - 群组列表
- **GET** `/api/admin/groups/{id}** - 群组详情
- **PUT** `/api/admin/groups/{id}** - 编辑群组
- **DELETE** `/api/admin/groups/{id}** - 解散群组

### 5.5 消息管理
- **GET** `/api/admin/messages** - 消息搜索
- **DELETE** `/api/admin/messages/{id}** - 删除消息

### 5.6 系统设置
- **GET** `/api/admin/settings** - 获取设置
- **PUT** `/api/admin/settings** - 更新设置

---

## 六、API响应格式

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
  "error_code": "VALIDATION_ERROR"
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

---

## 七、安全设计

### 7.1 认证
- JWT Token认证
- Token有效期: 7天
- Refresh Token: 30天

### 7.2 密码安全
- 密码加密: bcrypt
- 密码强度验证
- 登录失败限制: 5次/小时

### 7.3 权限控制
- 用户权限: 仅能操作自己的数据
- 管理员权限: 系统级操作
- 群主权限: 群管理操作

### 7.4 数据安全
- SQL注入防护: Prepared Statements
- XSS防护: 内容转义
- CSRF防护: Token验证

---

## 八、技术要求

### 8.1 PHP依赖
- PHP 8.2+
- PDO MySQL扩展
- Redis扩展
- JSON支持
- OpenSSL扩展

### 8.2 Composer包
- `firebase/php-jwt` - JWT处理
- `cboden/ratchet` - WebSocket服务
- `predis/predis` - Redis客户端

### 8.3 性能要求
- API响应时间: < 200ms
- 消息推送延迟: < 100ms
- 支持同时在线: 10000+

---

## 九、项目结构

```
/im-system
├── /app
│   ├── /Controllers
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── FriendController.php
│   │   ├── MessageController.php
│   │   ├── GroupController.php
│   │   └── AdminController.php
│   ├── /Models
│   │   ├── User.php
│   │   ├── Friendship.php
│   │   ├── PrivateMessage.php
│   │   ├── Group.php
│   │   ├── GroupMember.php
│   │   └── GroupMessage.php
│   ├── /Services
│   │   ├── AuthService.php
│   │   ├── MessageService.php
│   │   ├── GroupService.php
│   │   └── CacheService.php
│   ├── /Middleware
│   │   ├── AuthMiddleware.php
│   │   └── AdminMiddleware.php
│   └── /Utils
│       ├── Response.php
│       ├── Token.php
│       └── Validator.php
├── /config
│   ├── database.php
│   ├── redis.php
│   └── app.php
├── /public
│   ├── index.php
│   └── ws_server.php
├── /routes
│   └── api.php
├── /database
│   └── schema.sql
├── /tests
└── composer.json
```
