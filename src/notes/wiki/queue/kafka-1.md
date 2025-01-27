## kafka 搭建和接入



### 测试环境搭建

```
version: '3.3'
services:
  zookeeper:
    image: wurstmeister/zookeeper
    container_name: zookeeper
    ports:
      - "2181:2181"
    restart: always
  kafka1:
    image: wurstmeister/kafka
    depends_on: [ zookeeper ]
    container_name: kafka1
    ports:
      - "9091:9091"
    environment:
      HOSTNAME: kafka1
      KAFKA_BROKER_ID: 0
      KAFKA_ADVERTISED_LISTENERS: PLAINTEXT://kafka1:9091
      KAFKA_LISTENERS: PLAINTEXT://0.0.0.0:9091
      KAFKA_ZOOKEEPER_CONNECT: zookeeper:2181/kafka
    extra_hosts:
      kafka1: 10.1.2.7
  kafka2:
    image: wurstmeister/kafka
    depends_on: [ zookeeper ]
    container_name: kafka2
    ports:
      - "9092:9092"
    environment:
      HOSTNAME: kafka2
      KAFKA_BROKER_ID: 1
      KAFKA_ADVERTISED_LISTENERS: PLAINTEXT://kafka2:9092
      KAFKA_LISTENERS: PLAINTEXT://0.0.0.0:9092
      KAFKA_ZOOKEEPER_CONNECT: zookeeper:2181/kafka
    extra_hosts:
      kafka2: 10.1.2.7
  kafka3:
    image: wurstmeister/kafka
    depends_on: [ zookeeper ]
    container_name: kafka3
    ports:
      - "9093:9093"
    environment:
      HOSTNAME: kafka3
      KAFKA_BROKER_ID: 2
      KAFKA_ADVERTISED_LISTENERS: PLAINTEXT://kafka3:9093
      KAFKA_LISTENERS: PLAINTEXT://0.0.0.0:9093
      KAFKA_ZOOKEEPER_CONNECT: zookeeper:2181/kafka
    extra_hosts:
      kafka3: 10.1.2.7
```
### 启动环境
docker-compose up -d


### 创建测试topic
```
docker exec -it kafka1 /bin/bash
kafka-topics.sh --create --zookeeper 10.1.2.7:2181/kafka --replication-factor 1 --partitions 2 --topic testtopic
```



### topic命名规则
格式：Gateio-{业务线}-{实际的topic名称}-Topic
举例: Gateio-Social-UserCreatePost-Topic ------ 表示社交业务用户发帖事件的topic消息
说明：
1. 前缀固定： Gateio ，固定后缀： -Topic
2. {业务线}和{实际的topic名称}都用驼峰命名，且首字母大写


### 消费组命名规则
格式：Gateio-{业务线}-{实际的消费组名称}-Group
举例: Gateio-Social-OnUserCreatePost-Group
说明：
1. 前缀固定： Gateio ，固定后缀： -Group
2. {业务线}和{实际的消费组名称}都用驼峰命名，且首字母大写






