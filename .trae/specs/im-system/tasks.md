# 即时通讯系统 - 任务清单

## 项目准备阶段

### 任务 1: 项目基础搭建
- [x] 创建项目目录结构
- [x] 初始化 composer.json 并配置自动加载
- [x] 创建配置文件 (database.php, redis.php, app.php)
- [x] 创建公共入口文件 (public/index.php)

### 任务 2: 数据库设计
- [x] 创建数据库初始化脚本 schema.sql
- [x] 创建用户表 (users)
- [x] 创建好友关系表 (friendships)
- [x] 创建私聊消息表 (private_messages)
- [x] 创建群组表 (groups)
- [x] 创建群组成员表 (group_members)
- [x] 创建群聊消息表 (group_messages)
- [x] 创建管理员表 (admins)

## 核心功能实现

### 任务 3: 工具类与基础服务
- [x] 创建 Response 统一响应类
- [x] 创建 Token JWT处理类
- [x] 创建 Validator 验证类
- [x] 创建数据库连接类 (Database)
- [x] 创建 Redis 连接类 (Redis)
- [x] 创建 CacheService 缓存服务

### 任务 4: 数据模型层
- [x] 创建 User 模型 (CRUD + 认证方法)
- [x] 创建 Friendship 模型
- [x] 创建 PrivateMessage 模型
- [x] 创建 Group 模型
- [x] 创建 GroupMember 模型
- [x] 创建 GroupMessage 模型

### 任务 5: 认证模块
- [x] 创建 AuthController
  - [x] 用户注册接口 /api/auth/register
  - [x] 用户登录接口 /api/auth/login
  - [x] 用户登出接口 /api/auth/logout
  - [x] Token刷新接口 /api/auth/refresh
- [x] 创建 AuthService 认证服务
- [x] 创建 AuthMiddleware 认证中间件
- [x] 实现 JWT token 生成与验证
- [x] 实现 Redis 会话管理

### 任务 6: 用户模块
- [x] 创建 UserController
  - [x] 获取用户信息 /api/user/profile
  - [x] 更新用户信息 /api/user/profile
  - [x] 修改密码 /api/user/password
  - [x] 上传头像 /api/user/avatar
- [x] 实现用户在线状态管理

### 任务 7: 好友管理模块
- [x] 创建 FriendController
  - [x] 发送好友请求 /api/friends/request
  - [x] 处理好友请求 /api/friends/request/{id}
  - [x] 获取好友列表 /api/friends
  - [x] 删除好友 /api/friends/{friend_id}
  - [x] 搜索用户 /api/friends/search
- [x] 创建 FriendService 好友服务
- [x] 实现好友请求通知推送

### 任务 8: 私聊消息模块
- [x] 创建 MessageController
  - [x] 发送私信 /api/message/private
  - [x] 获取历史消息 /api/message/private/history/{user_id}
  - [x] 标记已读 /api/message/private/read/{user_id}
  - [x] 获取未读数 /api/message/private/unread
- [x] 创建 MessageService 消息服务
- [x] 实现消息存储与缓存
- [x] 实现未读消息计数

### 任务 9: 群聊模块
- [x] 创建 GroupController
  - [x] 创建群组 /api/group
  - [x] 获取群组列表 /api/group
  - [x] 获取群组详情 /api/group/{id}
  - [x] 加入群组 /api/group/{id}/join
  - [x] 退出群组 /api/group/{id}/leave
  - [x] 设置管理员 /api/group/{id}/admin/{user_id}
  - [x] 发送群消息 /api/group/{id}/message
  - [x] 获取群消息历史 /api/group/{id}/message
- [x] 创建 GroupService 群组服务
- [x] 实现群成员权限管理
- [x] 实现群消息缓存

## WebSocket实时通信

### 任务 10: WebSocket服务器
- [x] 创建 WebSocket 服务器 (ws_server.php)
- [x] 实现连接认证 (Token验证)
- [x] 实现私聊消息推送
- [x] 实现群聊消息推送
- [x] 实现用户状态同步
- [x] 实现正在输入状态
- [x] 实现心跳检测机制

### 任务 11: WebSocket客户端处理器
- [x] 创建 MessageHandler 消息处理器
- [x] 创建 ConnectionManager 连接管理器
- [x] 实现消息路由

## 管理员后台

### 任务 12: 管理员认证
- [x] 创建 AdminController
  - [x] 管理员登录 /api/admin/login
  - [x] 管理员登出 /api/admin/logout
- [x] 创建 AdminMiddleware 管理员中间件
- [x] 实现管理员权限验证

### 任务 13: 管理员功能
- [x] 仪表盘统计 /api/admin/dashboard
- [x] 用户管理接口 (CRUD + 封禁)
- [x] 群组管理接口 (CRUD + 解散)
- [x] 消息管理接口 (搜索 + 删除)
- [x] 系统设置接口

## 测试与部署

### 任务 14: API路由配置
- [x] 创建 routes/api.php 路由文件
- [x] 配置所有API路由
- [x] 配置路由中间件

### 任务 15: 系统测试
- [x] 创建基础测试用例
- [x] 测试用户认证流程
- [x] 测试好友功能
- [x] 测试私聊功能
- [x] 测试群聊功能
- [x] 测试WebSocket连接

---

## 任务依赖关系

```
任务 1 (项目搭建)
    ↓
任务 2 (数据库)
    ↓
任务 3 (工具类) ←→ 任务 4 (数据模型)
    ↓
任务 5 (认证模块) ← 任务 3
    ↓
任务 6-9 (业务模块) ← 任务 4, 5
    ↓
任务 10-11 (WebSocket) ← 任务 5, 8, 9
    ↓
任务 12-13 (管理员后台) ← 任务 3, 5
    ↓
任务 14-15 (路由 + 测试)
```
