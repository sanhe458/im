# 即时通讯系统 - 任务清单

## 项目准备阶段

### 任务 1: 项目基础搭建
- [ ] 创建项目目录结构
- [ ] 初始化 composer.json 并配置自动加载
- [ ] 创建配置文件 (database.php, redis.php, app.php)
- [ ] 创建公共入口文件 (public/index.php)

### 任务 2: 数据库设计
- [ ] 创建数据库初始化脚本 schema.sql
- [ ] 创建用户表 (users)
- [ ] 创建好友关系表 (friendships)
- [ ] 创建私聊消息表 (private_messages)
- [ ] 创建群组表 (groups)
- [ ] 创建群组成员表 (group_members)
- [ ] 创建群聊消息表 (group_messages)
- [ ] 创建管理员表 (admins)

## 核心功能实现

### 任务 3: 工具类与基础服务
- [ ] 创建 Response 统一响应类
- [ ] 创建 Token JWT处理类
- [ ] 创建 Validator 验证类
- [ ] 创建数据库连接类 (Database)
- [ ] 创建 Redis 连接类 (Redis)
- [ ] 创建 CacheService 缓存服务

### 任务 4: 数据模型层
- [ ] 创建 User 模型 (CRUD + 认证方法)
- [ ] 创建 Friendship 模型
- [ ] 创建 PrivateMessage 模型
- [ ] 创建 Group 模型
- [ ] 创建 GroupMember 模型
- [ ] 创建 GroupMessage 模型

### 任务 5: 认证模块
- [ ] 创建 AuthController
  - [ ] 用户注册接口 /api/auth/register
  - [ ] 用户登录接口 /api/auth/login
  - [ ] 用户登出接口 /api/auth/logout
  - [ ] Token刷新接口 /api/auth/refresh
- [ ] 创建 AuthService 认证服务
- [ ] 创建 AuthMiddleware 认证中间件
- [ ] 实现 JWT token 生成与验证
- [ ] 实现 Redis 会话管理

### 任务 6: 用户模块
- [ ] 创建 UserController
  - [ ] 获取用户信息 /api/user/profile
  - [ ] 更新用户信息 /api/user/profile
  - [ ] 修改密码 /api/user/password
  - [ ] 上传头像 /api/user/avatar
- [ ] 实现用户在线状态管理

### 任务 7: 好友管理模块
- [ ] 创建 FriendController
  - [ ] 发送好友请求 /api/friends/request
  - [ ] 处理好友请求 /api/friends/request/{id}
  - [ ] 获取好友列表 /api/friends
  - [ ] 删除好友 /api/friends/{friend_id}
  - [ ] 搜索用户 /api/friends/search
- [ ] 创建 FriendService 好友服务
- [ ] 实现好友请求通知推送

### 任务 8: 私聊消息模块
- [ ] 创建 MessageController
  - [ ] 发送私信 /api/message/private
  - [ ] 获取历史消息 /api/message/private/history/{user_id}
  - [ ] 标记已读 /api/message/private/read/{user_id}
  - [ ] 获取未读数 /api/message/private/unread
- [ ] 创建 MessageService 消息服务
- [ ] 实现消息存储与缓存
- [ ] 实现未读消息计数

### 任务 9: 群聊模块
- [ ] 创建 GroupController
  - [ ] 创建群组 /api/group
  - [ ] 获取群组列表 /api/group
  - [ ] 获取群组详情 /api/group/{id}
  - [ ] 加入群组 /api/group/{id}/join
  - [ ] 退出群组 /api/group/{id}/leave
  - [ ] 设置管理员 /api/group/{id}/admin/{user_id}
  - [ ] 发送群消息 /api/group/{id}/message
  - [ ] 获取群消息历史 /api/group/{id}/message
- [ ] 创建 GroupService 群组服务
- [ ] 实现群成员权限管理
- [ ] 实现群消息缓存

## WebSocket实时通信

### 任务 10: WebSocket服务器
- [ ] 创建 WebSocket 服务器 (ws_server.php)
- [ ] 实现连接认证 (Token验证)
- [ ] 实现私聊消息推送
- [ ] 实现群聊消息推送
- [ ] 实现用户状态同步
- [ ] 实现正在输入状态
- [ ] 实现心跳检测机制

### 任务 11: WebSocket客户端处理器
- [ ] 创建 MessageHandler 消息处理器
- [ ] 创建 ConnectionManager 连接管理器
- [ ] 实现消息路由

## 管理员后台

### 任务 12: 管理员认证
- [ ] 创建 AdminController
  - [ ] 管理员登录 /api/admin/login
  - [ ] 管理员登出 /api/admin/logout
- [ ] 创建 AdminMiddleware 管理员中间件
- [ ] 实现管理员权限验证

### 任务 13: 管理员功能
- [ ] 仪表盘统计 /api/admin/dashboard
- [ ] 用户管理接口 (CRUD + 封禁)
- [ ] 群组管理接口 (CRUD + 解散)
- [ ] 消息管理接口 (搜索 + 删除)
- [ ] 系统设置接口

## 测试与部署

### 任务 14: API路由配置
- [ ] 创建 routes/api.php 路由文件
- [ ] 配置所有API路由
- [ ] 配置路由中间件

### 任务 15: 系统测试
- [ ] 创建基础测试用例
- [ ] 测试用户认证流程
- [ ] 测试好友功能
- [ ] 测试私聊功能
- [ ] 测试群聊功能
- [ ] 测试WebSocket连接

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
