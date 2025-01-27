# Canal 同步 用户昵称/头像

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
9. 访问https://canal-admin.fulltrust.link:9090/ ，预期如下




前置条件
1. canal-1和canal-2都已部署canal-server，并且都能够正常连接zk集群
2. canal-admin正常启动，能够访问


数据表创建
在point实例中创建 canal_demo数据库和canal_demo_user_test数据表。具体命令如下
mysql> create database canal_demo;
Query OK, 1 row affected (0.01 sec)

mysql> use canal_demo;
Database changed
mysql> CREATE TABLE `canal_demo_user_test` (
    ->   `id` int NOT NULL AUTO_INCREMENT,
    ->   `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    ->   `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
    ->   `birthdate` date DEFAULT NULL,
    ->   `is_active` tinyint(1) DEFAULT '1',
    ->   `password` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    ->   PRIMARY KEY (`id`)
    -> ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
Query OK, 0 rows affected (0.04 sec)

mysql> INSERT INTO `canal_demo_user_test` (`username`, `email`, `birthdate`, `is_active`, `password`)
    -> VALUES
    -> ('john_doe', 'john@example.com', '1985-06-15', 1, 'password123'),
    -> ('jane_smith', 'jane@example.com', '1990-04-22', 1, 'securepassword'),
    -> ('sam_wilson', 'sam@example.com', '1992-08-30', 1, 'mypassword'),
    -> ('lisa_brown', 'lisa@example.com', '1988-12-05', 1, 'password456'),
    -> ('mark_jones', 'mark@example.com', '1995-01-20', 1, 'anotherpassword');
Query OK, 5 rows affected (0.00 sec)
Records: 5  Duplicates: 0  Warnings: 0





Kafka集群创建测试Topic
1. 创建Canal-Demo-Topictopic
bin/kafka-topics.sh --create --topic Canal-Demo-Topic --bootstrap-server b-1.phpservice.b4wfp4.c2.kafka.ap-northeast-1.amazonaws.com:9092 --partitions 3 --replication-factor 2
2. 查看topic是否创建成功，预期应该能看到Canal-Demo-Topic
bin/kafka-topics.sh --list --bootstrap-server b-1.phpservice.b4wfp4.c2.kafka.ap-northeast-1.amazonaws.com:9092





数据变更投递到kafka实验
消费脚本开启
在canal-2运行如下命令：
bin/kafka-console-consumer.sh --bootstrap-server b-1.phpservice.b4wfp4.c2.kafka.ap-northeast-1.amazonaws.com:9092 --topic Canal-Demo-Topic --from-beginning --group My-Canal-Demo-Consume-Group
执行成功后，程序会停在那里，监听Canal-Demo-Topic消息





插入数据测试
1. 插入单条数据
  1. 测试sql语句：
INSERT INTO `canal_demo_user_test` (`username`, `email`, `birthdate`, `is_active`, `password`)
VALUES ('charlie_brown', 'charlie@example.com', '1992-10-12', 1, 'charliesecure');
  2. 消费到的Topic消息内容:
{"data":[{"id":"8","username":"charlie_brown","email":"charlie@example.com","birthdate":"1992-10-12","is_active":"1"}],"database":"canal_demo","es":1719906626000,"gtid":"","id":2006,"isDdl":false,"mysqlType":{"id":"int(11)","username":"varchar(50)","email":"varchar(100)","birthdate":"date","is_active":"tinyint(1)"},"old":null,"pkNames":["id"],"sql":"","sqlType":{"id":4,"username":12,"email":12,"birthdate":91,"is_active":-6},"table":"canal_demo_user_test","ts":1719906626256,"type":"INSERT"}
注意：此时 "type":"INSERT"，data字段为数组，里面只有一条数据
说明：data数组里面的单条数据，和插入的数据一样的（排除敏感字段）

备注：对于上述内容如果仔细观察会发现，data数据中是没有password的值的，这个是因为我们故意这样配置的，把敏感信息给屏蔽掉了，不予显示。也就意味着下面的实验结果都会没有password字段，下面便不再赘述，详细的会在【敏感数据测试】这一节会介绍

2. 插入多条数据
  1. 测试sql语句：
INSERT INTO `canal_demo_user_test` (`username`, `email`, `birthdate`, `is_active`, `password`)
VALUES
('diana_prince', 'diana@example.com', '1987-11-30', 1, 'dianapassword'),
('eve_adams', 'eve@example.com', '1995-05-14', 1, 'eve12345');
  2. 消费到的Topic消息内容:
{"data":[{"id":"9","username":"diana_prince","email":"diana@example.com","birthdate":"1987-11-30","is_active":"1"},{"id":"10","username":"eve_adams","email":"eve@example.com","birthdate":"1995-05-14","is_active":"1"}],"database":"canal_demo","es":1719916527000,"gtid":"","id":3668,"isDdl":false,"mysqlType":{"id":"int(11)","username":"varchar(50)","email":"varchar(100)","birthdate":"date","is_active":"tinyint(1)"},"old":null,"pkNames":["id"],"sql":"","sqlType":{"id":4,"username":12,"email":12,"birthdate":91,"is_active":-6},"table":"canal_demo_user_test","ts":1719916527849,"type":"INSERT"}
注意：此时 "type":"INSERT"，data字段为数组，里面有两条数据，跟插入数据条数一致
说明：data数组里面的两条数据，和插入的数据一样的（排除敏感字段）
更新数据测试
1. 更新单条数据
  1. 测试sql语句：
update canal_demo_user_test set is_active=0,birthdate='1995-05-10' where id=10;
  2. 消费到的Topic消息内容:
{"data":[{"id":"10","username":"eve_adams","email":"eve@example.com","birthdate":"1995-05-10","is_active":"0"}],"database":"canal_demo","es":1719916925000,"gtid":"","id":3736,"isDdl":false,"mysqlType":{"id":"int(11)","username":"varchar(50)","email":"varchar(100)","birthdate":"date","is_active":"tinyint(1)"},"old":[{"birthdate":"1995-05-14","is_active":"1"}],"pkNames":["id"],"sql":"","sqlType":{"id":4,"username":12,"email":12,"birthdate":91,"is_active":-6},"table":"canal_demo_user_test","ts":1719916925954,"type":"UPDATE"}
注意：此时 "type":"UPDATE"，data字段为数组，里面只有一条数据
说明：
  - data数组包含一条数据，表示目前id=10的这条数据最新记录是什么，可以看到，除了sql语句中更新的字段，还包含除了敏感字段的其他字段
  - 具体字段更新之前的值在 "old"字段里面体现，如下所示，表示更新之前birthdate和is_active字段的值
"old":[{"birthdate":"1995-05-14","is_active":"1"}]
2. 更新多条数据
  原始数据对比参考：
select * from canal_demo_user_test where id in (8,9);
+----+---------------+---------------------+------------+-----------+---------------+
| id | username      | email               | birthdate  | is_active | password      |
+----+---------------+---------------------+------------+-----------+---------------+
|  8 | charlie_brown | charlie@example.com | 1992-10-12 |         1 | charliesecure |
|  9 | diana_prince  | diana@example.com   | 1987-11-30 |         1 | dianapassword |
+----+---------------+---------------------+------------+-----------+---------------+
  1. 测试sql语句：
update canal_demo_user_test set is_active=0,birthdate='1995-08-08' where id in(8,9);
  2. 消费到的Topic消息内容:
{"data":[{"id":"8","username":"charlie_brown","email":"charlie@example.com","birthdate":"1995-08-08","is_active":"0"},{"id":"9","username":"diana_prince","email":"diana@example.com","birthdate":"1995-08-08","is_active":"0"}],"database":"canal_demo","es":1719922093000,"gtid":"","id":4600,"isDdl":false,"mysqlType":{"id":"int(11)","username":"varchar(50)","email":"varchar(100)","birthdate":"date","is_active":"tinyint(1)"},"old":[{"birthdate":"1992-10-12","is_active":"1"},{"birthdate":"1987-11-30","is_active":"1"}],"pkNames":["id"],"sql":"","sqlType":{"id":4,"username":12,"email":12,"birthdate":91,"is_active":-6},"table":"canal_demo_user_test","ts":1719922094144,"type":"UPDATE"}
  注意：此时 "type":"UPDATE"，data字段为数组，里面只有两条数据
      说明：
  - data数组包含两条数据，分别表示id=8和id=9对应的最新数据
  - 具体字段更新之前的值在 "old"字段里面体现，如下所示：
"old":[{"birthdate":"1992-10-12","is_active":"1"},{"birthdate":"1987-11-30","is_active":"1"}]
  可见old数组包含两条数据，其和data数组索引一一对应，即：
    第一条数据{"birthdate":"1992-10-12","is_active":"1"}对应id=8更新之前birthdate和is_active的值
    第二条数据{"birthdate":"1987-11-30","is_active":"1"}对应id=9更新之前的birthdate和is_active的值
删除数据测试
1. 删除单条数据
  1. 测试sql语句：
delete from canal_demo_user_test where id=1;
  2. 消费到的Topic消息内容:
{"data":[{"id":"1","username":"john_doe","email":"john@example.com","birthdate":"1985-06-15","is_active":"0"}],"database":"canal_demo","es":1719923236000,"gtid":"","id":4796,"isDdl":false,"mysqlType":{"id":"int(11)","username":"varchar(50)","email":"varchar(100)","birthdate":"date","is_active":"tinyint(1)"},"old":null,"pkNames":["id"],"sql":"","sqlType":{"id":4,"username":12,"email":12,"birthdate":91,"is_active":-6},"table":"canal_demo_user_test","ts":1719923236583,"type":"DELETE"}
  注意：此时 "type":"DELETE"，data字段为数组，里面只有一条数据，表示删除时id=1对应的数据
2. 删除多条数据
  1. 测试sql语句：
delete from canal_demo_user_test where id in(2,3);
  2. 消费到的Topic消息内容:
{"data":[{"id":"2","username":"jane_smith","email":"jane@example.com","birthdate":"1990-04-22","is_active":"0"},{"id":"3","username":"sam_wilson","email":"sam@example.com","birthdate":"1992-08-30","is_active":"0"}],"database":"canal_demo","es":1719923976000,"gtid":"","id":4922,"isDdl":false,"mysqlType":{"id":"int(11)","username":"varchar(50)","email":"varchar(100)","birthdate":"date","is_active":"tinyint(1)"},"old":null,"pkNames":["id"],"sql":"","sqlType":{"id":4,"username":12,"email":12,"birthdate":91,"is_active":-6},"table":"canal_demo_user_test","ts":1719923976530,"type":"DELETE"}
注意：此时 "type":"DELETE"，data字段为数组，里面含有两条数据，表示删除id=2和id=3时对应的数据






敏感数据测试
其实从上述实验结果能看到，数据变更推送到Kafka中的数据，已经过滤掉了敏感字段password，这主要是通过在canal中配置要过滤的字段名单做到的。具体配置如下:
canal.instance.filter.black.field=canal_demo.canal_demo_user_test:password
1. 实验
现在我们对email这个字段，也认为是敏感信息，需要进行屏蔽，则可以通过如下配置进行测试：
canal.instance.filter.black.field=canal_demo.canal_demo_user_test:password/email
  1. 原始数据
select * from canal_demo_user_test where id=4;
+----+------------+------------------+------------+-----------+-------------+
| id | username   | email            | birthdate  | is_active | password    |
+----+------------+------------------+------------+-----------+-------------+
|  4 | lisa_brown | lisa@example.com | 1988-12-05 |         0 | password456 |
+----+------------+------------------+------------+-----------+-------------+
  2. 测试sql语句：
update canal_demo_user_test set email='lisa_demo@example.com',password='lisaPassword12345' where id=4;
  3. 消费到的Topic消息内容:
{"data":[{"id":"4","username":"lisa_brown","birthdate":"1988-12-05","is_active":"0"}],"database":"canal_demo","es":1719924966000,"gtid":"","id":28,"isDdl":false,"mysqlType":{"id":"int(11)","username":"varchar(50)","birthdate":"date","is_active":"tinyint(1)"},"old":[{}],"pkNames":["id"],"sql":"","sqlType":{"id":4,"username":12,"birthdate":91,"is_active":-6},"table":"canal_demo_user_test","ts":1719924966292,"type":"UPDATE"}
  如上所示，上述data数组中的数据中，看不到敏感字段 password和email的值
  4. 再次查看id=4的原始数据，如下所示:
 select * from canal_demo_user_test where id=4;
+----+------------+-----------------------+------------+-----------+-------------------+
| id | username   | email                 | birthdate  | is_active | password          |
+----+------------+-----------------------+------------+-----------+-------------------+
|  4 | lisa_brown | lisa_demo@example.com | 1988-12-05 |         0 | lisaPassword12345 |
+----+------------+-----------------------+------------+-----------+-------------------+
2. 配置规则参考
# table field black filter(format: schema1.tableName1:field1/field2,schema2.tableName2:field1/field2)
#canal.instance.filter.black.field=test1.t_product:subject/product_image,test2.t_company:id/name/contact/ch
集群高可用实验
目前是在canal-1和canal-2上面都部署了canal-server，组成了集群模式，其中一台机器（在我们实验场景中是canal-2)是作为standby的角色，当另外一台canal机器不可用时，则由这台standby的机器对外提供服务，从而保证整体canal服务的可用性。
因此，我们需要对上述场景进行实验模拟，确保我们搭建的集群能够按照我们预期的方式运行。
停止canal-server测试
1. 停止之前admin界面截图
[图片]

[图片]
如图所示：两台canal机器上canal服务都正常运行，且canal_demo这个instance所属主机是gateio_canal_server_01，也就是canal-1这台机器
2. 停止canal-1这台机器上的canal服务
#登录到canal-1
#执行如下命令
sudo systemctl stop canal
3. 执行步骤2之后的canal-admin界面截图
[图片]
[图片]
如上图所示：server名称为gateio_canal_server_01（对应canal-1机器）一机构处于断开状态，但是我们的canal_demo实例仍然运行正常，只是此时所属主机已经切换到gateio_canal_server_02
4. 测试数据变更通知到kafka是否依然正常
  1. 测试sql语句：
update canal_demo_user_test set is_active=0,birthdate='1995-10-10' where id in(5,6);
  2. 消费到的Topic消息内容:
{"data":[{"id":"5","username":"mark_jones","birthdate":"1995-10-10","is_active":"0"},{"id":"6","username":"alice_green","birthdate":"1995-10-10","is_active":"0"}],"database":"canal_demo","es":1719926802000,"gtid":"","id":157,"isDdl":false,"mysqlType":{"id":"int(11)","username":"varchar(50)","birthdate":"date","is_active":"tinyint(1)"},"old":[{"birthdate":"1995-01-20","is_active":"1"},{"birthdate":"1993-11-11","is_active":"1"}],"pkNames":["id"],"sql":"","sqlType":{"id":4,"username":12,"birthdate":91,"is_active":-6},"table":"canal_demo_user_test","ts":1719926802449,"type":"UPDATE"}
可以看到，Kafka中仍然能够收到Topic消息，说明 数据变更通知-->canal-->Kafka这条链路是正常工作的，也表明canal_demo这个instance工作正常
5. 测试过程中也出现了canal-1不可用的告警，如下图所示:
[图片]
6. 启动canal-1上的canal，停止canal-2上的canal
  1. 启动canal-1上的canal
#登录到canal-1机器,执行如下命令
sudo systemctl start canal
[图片]
[图片]
  可以看到，虽然canal-1上的canal服务启动起来了，但是canal_demo这个instance所属的主机仍然为gateio_canal_server_02
  2. 停止canal-2上的canal
sudo systemctl stop canal
  3. 执行之后canal-amdin界面截图如下
[图片]
[图片]
  如上图所示，canal-2机器上的canal停止，canal_demo这个instance自动切换到gateio_canal_server_01（canal-1机器上的canal），仍然正常工作
  
7. canal-2上的canal服务停止后，也出现对应告警：
[图片]
8. 测试数据变更通知到kafka是否依然正常
  1. 测试sql语句：
update canal_demo_user_test set is_active=1,birthdate='1995-11-11' where id in(5,6);
  2. 消费到的Topic消息内容:
{"data":[{"id":"5","username":"mark_jones","birthdate":"1995-11-11","is_active":"1"},{"id":"6","username":"alice_green","birthdate":"1995-11-11","is_active":"1"}],"database":"canal_demo","es":1719928262000,"gtid":"","id":50,"isDdl":false,"mysqlType":{"id":"int(11)","username":"varchar(50)","birthdate":"date","is_active":"tinyint(1)"},"old":[{"birthdate":"1995-10-10","is_active":"0"},{"birthdate":"1995-10-10","is_active":"0"}],"pkNames":["id"],"sql":"","sqlType":{"id":4,"username":12,"birthdate":91,"is_active":-6},"table":"canal_demo_user_test","ts":1719928262695,"type":"UPDATE"}
9. 启动canal-2上的canal，恢复集群到最初状态
#登录到canal-2机器，执行如下命令
sudo systemctl start canal
10. 执行后，canal-admin界面截图如下：
[图片]
[图片]




停止canal-admin测试
理论上停止canal-admin对canal服务没有影响，因为admin是管理集群的后台，而canal运行所需的配置和高可用机制是依赖zk集群实现的。虽然分析是这样，但是我们仍然需要停止canal-admin服务，来测试验证是否对整条通知链路有影响
1. 停止canal-admin
#登录到canal-2机器，执行如下命令
sudo systemctl stop canal-admin
2. 执行后访问 https://canal-admin.fulltrust.link:9090/，发现把502错误，符合预期，如下图所示
[图片]
3. 测试数据变更通知到kafka是否依然正常
  1. 测试sql语句：
update canal_demo_user_test set is_active=1,birthdate='1995-09-09' where id in(5,6);
  2. 消费到的Topic消息内容:
{"data":[{"id":"5","username":"mark_jones","birthdate":"1995-09-09","is_active":"1"},{"id":"6","username":"alice_green","birthdate":"1995-09-09","is_active":"1"}],"database":"canal_demo","es":1719929252000,"gtid":"","id":218,"isDdl":false,"mysqlType":{"id":"int(11)","username":"varchar(50)","birthdate":"date","is_active":"tinyint(1)"},"old":[{"birthdate":"1995-11-11"},{"birthdate":"1995-11-11"}],"pkNames":["id"],"sql":"","sqlType":{"id":4,"username":12,"birthdate":91,"is_active":-6},"table":"canal_demo_user_test","ts":1719929253002,"type":"UPDATE"}
如上所示，通知依然正常，也就说明停止canal-admin对canal服务运行没有任何影响
所遇问题
1. 监听到创建其他数据库的消息
测试过程中消费Canal-Demo-Topic的消息发现，出现了一条如下所示的消息：
{"data":null,"database":"rebate_realtime_test","es":1719824686000,"gtid":"","id":43888,"isDdl":true,"mysqlType":null,"old":null,"pkNames":null,"sql":"create database rebate_realtime_test","sqlType":null,"table":"","ts":1719824686784,"type":"QUERY"}
通过消息内容，我们可以发现，此消息是 create database rebate_realtime_test这个事件触发引起的，但是我们明明在canal_demo这个instance的配置中配置了只监听canal_demo库的canal_demo_user_test表的数据表更，查阅官方资料发现通过修改对应配置为如下内容即可解决：
#是否忽略DCL的query语句，比如grant/create user等，默认为false，我们改为true
canal.instance.filter.query.dcl=true 
2. 监听到对表canal_demo_user_test进行字段的增加变更消息
  1. 实验sql如下
ALTER TABLE canal_demo_user_test ADD COLUMN login_time DATETIME;
  2. 消费到的Topic消息内容:
{"data":null,"database":"canal_demo","es":1719931675000,"gtid":"","id":628,"isDdl":true,"mysqlType":null,"old":null,"pkNames":null,"sql":"ALTER TABLE canal_demo_user_test ADD COLUMN login_time DATETIME","sqlType":null,"table":"canal_demo_user_test","ts":1719931676148,"type":"ALTER"}
表的ddl变化，我们预期是不关心的，实际使用中是要忽略此消息，同上，我们仍然可以通过修改配置文件达到目的，使其发生ddl的时候，不发送消息到Kakka中。如下所示：
#是否忽略DDL的query语句，比如create table/alater table/drop table/rename table/create index/drop index. (目前支持的ddl类型主要为table级别的操作，create databases/trigger/procedure暂时划分为dcl类型)
#默认为false，我们设置为true
canal.instance.filter.query.ddl=true 





