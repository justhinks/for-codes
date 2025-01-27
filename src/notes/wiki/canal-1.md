# Canal 支持 用户昵称/头像

背景
其他业务线有对用户的昵称，头像等信息存储的，当用户信息变更时，需要能够较为及时的去同步更新。基于此需求，故准备引入canal方案。

前置准备
机器规格：2台 4c 8G + 100G 机器 （走申请流程）
操作系统版本：  Ubuntu 22.04.4 LTS
目的：用于搭建 canal 集群模式
步骤
主机名设置
申请到的两台机器，分别命名为canal-1和canal-2

安装mysql
1. 在canal-2这台机器上，安装mysql，给canal-admin使用，步骤如下:
sudo apt install mysql-server
sudo mysql_secure_installation #执行mysql安全校验，基本一路选择y
sudo mysql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '自行设置的root密码';
2. 给canal-admin项目使用mysql创建特定用户，步骤如下：
sudo mysql -uroot -p 
#输入上面自行设置的密码
#登录成功后，执行下面命令

CREATE USER 'canal-admin'@'localhost' IDENTIFIED BY '自行设置的canal-admin用户密码';
GRANT ALL PRIVILEGES ON canal_manager.* TO 'canal-admin'@'localhost';
#这里主要目的是假设后续canal-admin项目部署到canal-1机器，也能连接到这里的mysql
CREATE USER 'canal-admin'@'172.31.%' IDENTIFIED BY '自行设置的canal-admin用户密码';
GRANT ALL PRIVILEGES ON canal_manager.* TO 'canal-admin'@'172.31.%'; 
FLUSH PRIVILEGES;
安装jdk
在canal-1和canal-2两台机器上，分别执行如下命令:
sudo apt install -y openjdk-11-jdk
jave --version 
#java --version 预期输出
# openjdk 11.0.23 2024-04-16 
# xxxxx

安装canal-admin
以下步骤在canal-2这台机器上进行
1. 下载canal-admin & 解压到/usr/local/canal-admin 目录
sudo mkdir -p /usr/local/canal-admin
mkdir -p ~/download
cd ~/download
wget https://github.com/alibaba/canal/releases/download/canal-1.1.7/canal.admin-1.1.7.tar.gz
sudo tar -zxvf canal.admin-1.1.7.tar.gz /usr/local/canal-admin
2. 导入canal-admin项目本身提供的sql文件
sudo mysql -uroot -p #登录到mysql
source /usr/local/canal-admin/conf/canal_manager.sql;
3. 编辑/usr/local/canal-admin/conf/application.yml文件，对应字段修改为以下内容：
server:
  port: 9090 #端口变更为9090
  
spring.datasource:
  address: 127.0.0.1:3306
  database: canal_manager
  username: canal-admin 
  password: '{自行设置的canal-admin用户密码}'
4. 保存上述文件，启动canal-admin
cd /usr/local/canal-admin
sudo bash bin/startup.sh
5. 查看进程是否存在，日志是否有报错等
ps -ef | grep canal-admin
tail -f /usr/local/canal-admin/logs/admin.log
6. 执行 curl -I 'http://127.0.0.1:9090'是否能够访问通canal-admin界面，预期如下:
curl -I 'http://127.0.0.1:9090'
HTTP/1.1 200
Vary: Origin
Vary: Access-Control-Request-Method
Vary: Access-Control-Request-Headers
Last-Modified: Mon, 09 Oct 2023 06:28:47 GMT
Accept-Ranges: bytes
Content-Type: text/html;charset=UTF-8
Content-Language: en-US
Content-Length: 4818
Date: Mon, 01 Jul 2024 13:57:38 GMT
7. 打通外部访问canal-admin的地址，找运维申请，预期的外部访问地址如下:
https://canal-admin.fulltrust.link:9090/ （目前访问已打通）

8. 使用systemd管理canal-admin项目，具体步骤如下:
  1. 新增文件/lib/systemd/system/canal-admin.service，内容如下：
[Unit]
Description=canal-admin
After=network.target

[Service]
Type=forking
Restart=always
ExecStart=/usr/local/canal-admin/bin/startup.sh
ExecStop=/usr/local/canal-admin/bin/stop.sh
ExecStopPost=/usr/local/canal-admin/bin/stop.sh

[Install]
WantedBy=multi-user.target
  2. 停止之前运行的canal-admin：sudo bash /usr/local/canal-admin/bin/stop.sh
  3. sudo systemctl start canal-admin
  4. sudo systemctl status canal-admin，预期输出如下:
canal-admin.service - canal-admin
     Loaded: loaded (/lib/systemd/system/canal-admin.service; enabled; vendor preset: enabled)
     Active: active (running) since Mon 2024-07-01 14:15:32 UTC; 1min 43s ago
     ...
  5. 开启设置启动canal-admin：sudo systemctl enable canal-admin
9. 访问https://canal-admin.fulltrust.link:9090/ ，预期如下所示: