# IM系统安装程序实施计划

## 摘要
为 IM 即时通讯系统创建一个交互式安装程序 (install.php)，提供数据库配置和管理员设置功能，并使用安装锁文件防止重复安装。

## 当前状态分析
- 项目位于 `/workspace/im-system/`
- 数据库结构定义在 `database/schema.sql`
- 默认管理员密码硬编码在 SQL 文件中
- 配置文件位于 `config/` 目录

## 实施计划

### 步骤 1: 创建 install.php 安装程序
**文件**: `/workspace/im-system/install.php`

**功能**:
1. **锁文件检测**
   - 检查 `install.lock` 文件是否存在
   - 若存在，显示"系统已安装"提示并阻止继续

2. **安装表单页面**
   - 数据库配置：主机、端口、数据库名、用户名、密码
   - Redis 配置：主机、端口、密码（可选）
   - 应用配置：JWT密钥（自动生成）
   - 管理员配置：用户名、密码

3. **安装处理流程**
   - 验证表单数据
   - 测试数据库连接
   - 写入配置文件
   - 执行数据库初始化
   - 创建管理员账户
   - 生成安装锁文件
   - 显示安装成功信息

4. **安全性**
   - CSRF 令牌保护
   - 密码强度验证
   - 输入数据过滤

### 步骤 2: 创建安装锁文件
**文件**: `/workspace/im-system/install.lock`
- 安装成功后自动创建
- 包含安装时间和环境信息

### 步骤 3: 创建卸载/重装脚本（可选）
**文件**: `/workspace/im-system/uninstall.php`
- 删除 install.lock 文件
- 提供重新安装功能

## 实现细节

### install.php 结构
```
├── 检测 install.lock
├── 显示表单或错误页面
├── 处理 POST 请求
│   ├── 验证输入
│   ├── 测试数据库连接
│   ├── 生成配置文件
│   ├── 执行数据库脚本
│   ├── 创建管理员
│   └── 创建 install.lock
└── 显示结果
```

### 配置文件生成
- `config/database.php` - 数据库配置
- `config/redis.php` - Redis 配置
- `config/app.php` - 应用配置（含 JWT 密钥）

### 数据库初始化
- 连接数据库服务器
- 创建数据库（如果不存在）
- 读取并执行 `database/schema.sql` 中的所有建表语句
- 更新管理员账户的用户名和密码（替换默认的 admin/admin123）

## 验证步骤
1. 访问 install.php 显示安装表单
2. 填写数据库信息和管理员用户名密码
3. 提交表单后验证：
   - 配置文件正确生成（database.php, redis.php, app.php）
   - 数据库连接成功
   - 执行 database/schema.sql 自动创建所有表
   - 管理员账户按输入的用户名密码创建
   - install.lock 文件创建成功
4. 再次访问 install.php 确认被 install.lock 阻止安装

## 预期输出文件
- `/workspace/im-system/install.php` - 安装程序主文件
- `/workspace/im-system/install.lock` - 安装锁文件（安装后生成）
