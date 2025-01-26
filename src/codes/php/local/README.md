# 标准PHP项目框架

这是一个遵循PSR标准的PHP项目框架结构。

## 目录结构

```
├── src/            # 源代码目录
├── tests/          # 测试文件目录
├── config/         # 配置文件目录
├── public/         # 公共访问目录
├── composer.json   # Composer配置文件
└── README.md      # 项目说明文档
```

## 要求

- PHP >= 7.4
- Composer

## 安装

```bash
composer install
```

## 测试

```bash
composer test
```

## 开发规范

- 遵循PSR-4自动加载规范
- 遵循PSR-12编码规范
- 使用PHPUnit进行单元测试