## go-zero 快速搭建

### 打通本地和测试环境

我们本地开发时经常要连接测试环境的服务，比如 MySQL，Nacos 之类的，你可以本地用 Docker 启动这些服务，但是有点麻烦。所以我们可以通过 SSH tunnel 将本地端口映射到测试环境的服务端口上，来达到本地(VPN)环境下访问测试环境的目的
参考这个文档下载对应的密钥到 ~/.ssh 目录下，并创建一个config文件(如果没有)，记得修改密钥和配置文件的权限为 600，然后将如下内容复制进去。以后有其他测试环境的服务需要本地访问，都可以在这里添加本地端口转发

```
Host nat
  HostName 43.154.63.29
  Port 2223
  User nat
  ServerAliveInterval 10
  TCPKeepAlive yes
  ControlPersist yes
  ControlMaster auto
  ControlPath ~/.ssh/master_%r_%h_%p
  IdentityFile ~/.ssh/nat-dev.pem
  # nacos
  LocalForward 18848 10.1.20.11:8848
  LocalForward 19848 10.1.20.11:9848
  LocalForward 5432 10.1.2.8:5432
```

使用以下命令控制本地转发
1. 启动本地转发：ssh -fN nat
2. 查看本地转发状态：ssh -O check nat
3. 退出本地转发：ssh -O exit nat
4. 检查端口：nc -zv 127.0.0.1 18848

### 环境变量

二开版本的 go-zero(WBE 这边) 强依赖 Nacos，项目在启动时都至少需要如下所示的这四个环境变量：

```
NACOS_HOST
NACOS_PORT
NACOS_USERNAME
NACOS_PASSWORD
```

所以你需要保证服务启动时，项目至少可以读取到这四个环境变量，这里提供几种常用的配置方式

通过脚本，可以把这个命令写到 xx.sh，需要的时候执行一下

```
export NACOS_HOST=""
export NACOS_PORT=""
export NACOS_USERNAME=""
export NACOS_PASSWORD=""
```

也可以把第一步中脚本的内容写在 ~/.zshrc 或 ~/.bashrc 中，取决于你用的 shell 是什么

【推荐】VSCode 内集成：如果你使用 VSCode 开发，可以在项目根目录下创建 .vscode/launch.json, 打开这个空的launch.json 时，右下角应该会提示你添加配置，根据指引操作即可，最终大概类似这样，env 的部分需要手动填进去，这样可以使用 VSCode 内置的调试工具运行项目。

```
{
    // 使用 IntelliSense 了解相关属性。
    // 悬停以查看现有属性的描述。
    // 欲了解更多信息，请访问: https://go.microsoft.com/fwlink/?linkid=830387
    "version": "0.2.0",
    "configurations": [
        {
            "name": "服务名",
            "type": "go",
            "request": "launch",
            "mode": "auto",
            "program": "项目入口文件",
            "args": [
                "-f",
                "etc/配置文件.yaml"
            ],
            "env": {
                "NACOS_HOST": "127.0.0.1",
                "NACOS_PORT": "18848",
                "NACOS_USERNAME": "nacos_gate",
                "NACOS_PASSWORD": "nacos_gate",
            }
        }
    ]
}
```


### 私有仓库访问配置

有很多 Golang 的私有包，无法正常通过 go get直接获取，需要配置私有库的访问

1. 首先是 go 的 GOPRIVATE 设置
```
go env -w GOPRIVATE=bitbucket.org/gatebackend,bitbucket.org/gateio
```
2. 在 Bitbucket 仓库设置 ssh，通过 ssh 的方式拉取代码: https://bitbucket.org/account/settings/ssh-keys/ （注意：如果代码无法拉取，可能是VPN接入点的问题，此时可以切换接入点HongKong）
3. 配置 git，这样 Golang 在拉取代码的时候会自动使用 ssh 的方式访问这些私有库并拉取代码
    git config --global --add url."git@bitbucket.org:".insteadOf "https://bitbucket.org/"


### 安装开发工具

3.1 Golang
首先你需要安装 Golang，目前推荐使用的版本是 1.22，在 https://go.dev/dl/ 找到合适的版本和合适的系统，苹果芯片的 Mac 电脑可以选择类似 go1.22.9.darwin-arm64.pkg ，然后按照 https://go.dev/doc/install 的指引安装。或者可以直接通过 homebrew 安装
安装后把 GOPATH 添加到 PATH 中   
echo 'export PATH=`go env GOPATH`/bin:$PATH' >> ~/.zshrc

3.2 goctl
其次，你需要安装 goctl，go-zero 提供的一个命令行工具，用于生成各种模板代码的工具，开发过程中会使用的很多
如果你已经安装了 goctl，可以执行一下 goctl 命令，查看一下输出，来确保你安装的是我们内部二开的版本，并试着执行一下 goctl upgrade 来升级一下你的 goctl
注意这里安装的是内部二开的 goctl，很多同事在安装 goctl 时安装的是开源的版本，导致生成的代码不符合预期。

go install bitbucket.org/gatebackend/go-zero/tools/goctl@master
安装完成后，可以执行命令 goctl 看下输出，高亮区域显示的是我们内部的框架地址就表示安装的没有问题

```
$ goctl
A cli tool to generate api, zrpc, model code

GitHub: https://bitbucket.org/gatebackend/go-zero
Site:   https://go-zero.dev

Usage:
  goctl [command]

Available Commands:
  api               Generate api related files
  bug               Report a bug
  completion        Generate the autocompletion script for the specified shell
  docker            Generate Dockerfile
  env               Check or edit goctl environment
  gateway           gateway is a tool to generate gateway code
  help              Help about any command
  kube              Generate kubernetes files
  lint              Generate golangci lint files
  migrate           Migrate from tal-tech to zeromicro
  model             Generate model code
  quickstart        quickly start a project
  rpc               Generate rpc code
  template          Template operation
  upgrade           Upgrade goctl to latest version
  xxljob            Generate XXL-JOB executor code

Flags:
  -h, --help      help for goctl
  -v, --version   version for goctl

Use "goctl [command] --help" for more information about a command.
```


3.3 golangci-lint
不了解什么是 golangci-lint? 可以看下文档： https://golangci-lint.run/，
省流：代码风格 / Bug / 安全检测
补充一些上下文：通过 goctl 生成的模板代码中会包含一个 .golangci.yml 文件，这是 golangci-lint 的配置文件。当安装了 golangci-lint并在项目目录下执行 golangci-lint run  的时候，会以这个配置文件中声明的 linter 来检测我们的代码，并报告检测到的问题。
另外生成的模板代码中还包含一个 Makefile，其中会帮你下载 golangci-lint 到项目根目录下，这在后续配置 pre-commit 时会用到。
另外，这是官方提供的安装方式：
curl -sSfL https://raw.githubusercontent.com/golangci/golangci-lint/master/install.sh | sh -s -- -b $(go env GOPATH)/bin v1.59.0
或者使用 homebrew
brew install golangcli-lint@1.59.0
golangci-lint 会进行代码风格的检查，这方面可以借助 gofumpt 来自动为我们格式化代码, 参考官方的文档在你使用的编辑器中配置格式化工具为 gofumpt: https://github.com/mvdan/gofumpt?tab=readme-ov-file#installation



### 生成模板代码

安装了 goctl 以后，可以通过 goctl 快速生成项目脚手架。WBE 在默认的脚手架模板上做了一些调整，集成了一些基础库功能，同时最大程度上保持了 PHP 那边的传统，比如使用的端口，健康检查的地址，配置项的使用方式等。

要使用我们自定义的模板，需要拉取 skeleton-go 的代码到本地，因为我们的模板代码定义在这个仓库里。

git clone git@bitbucket.org:gateio/gateio_service_skeleton_go.git

下载以后就可以通过 goctl 来生成模板代码。这里通过 --home 指定了模板的目录为 skeleton_go 中定义的模板。
下面这个命令指定了 goctl 的模板为 skeleton 中自定义的模板，并且生成一个名为 demo 的项目

goctl api new demo --home ~/workspace/gateio/gateio_service_skeleton_go/template

```
.
├── .golangci.yml  golangci-ling 的配置文件
├── Makefile  安装和执行 golangci-lint
├── demo.api  在这里定义接口
├── demo.go  项目入口, 服务通过该文件启动
├── etc
│   └── demo-api.yaml  配置文件
├── go.mod
├── go.sum
└── internal                  
    ├── config                  
    │   └── config.go  声明配置的地方
    ├── handler  这个目录是由 goctl 通过 demo.api 文件自动生成
    │   ├── demohandler.go
    │   └── routes.go  声明接口路由的地方，目前这块有点坑，下面会提到
    ├── logic  这个目录是由 goctl 通过 demo.api 文件自动生成，也是开发业务逻辑的地方
    │   └── demologic.go  
    ├── svc
    │   └── servicecontext.go  在这里执行各种初始化动作, 比如 MySQL, Redis...
    └── types
        └── types.go  由 goctl 自动生成

8 directories, 11 files
```

可以看到，除了 demo-api.yaml 这个文件以外，其他文件名都是中间都是没有任何符号的，有些同事可能会觉得这样可读性不太好，希望单词之间用下划线分隔开来。可以通过 --style go_zero 来调整生成的文件命名方式，比如

goctl --style go_zero api new demo --home ~/workspace/gateio/gateio_service_skeleton_go/template


```
.
├── .golangci.yml
├── Makefile
├── demo.api
├── demo.go
├── etc
│   └── demo-api.yaml
├── go.mod
└── internal
    ├── config
    │   └── config.go
    ├── handler
    │   ├── demo_handler.go
    │   └── routes.go
    ├── logic
    │   └── demo_logic.go
    ├── svc
    │   └── service_context.go
    └── types
        └── types.go

8 directories, 12 files
```

文件的命名上，有无下划线都可以，不做强制约定。
进入到生成的项目内，执行 go mod tidy自动拉取依赖，如果上面搭建环境的步骤都正确操作了，这里应该不会有什么问题。
此时就可以初始化 git 并开始提交了 git init
建议在 .gitignore 中把 bin 目录加进去，因为 Makefile 会把 golangci-lint 下载到这个位置，容易误提交到 git 去。



### 配置 golangci-lint

安装的 golangci-lint 是可以集成到 git 的 pre-commit 中的，在你尝试执行 git commit 时会自动执行 lint 检查，必须完全通过才允许提交代码 :)
在初始化 git 仓库后，在项目根目录下编辑 pre-commit: vim .git/hooks/pre-commit，然后复制下面的内容

```
#!/bin/sh

# 变量设置
STAGED_GO_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.go$')
[ -z "$STAGED_GO_FILES" ] && exit 0

# 在所有已暂存的 Go 文件上运行 golangci-lint
echo "Running golangci-lint..."
make lint

# 检查 golangci-lint 的退出码
if [ $? != 0 ]; then
 echo "golangci-lint 检查失败, git commit 被取消。"
 exit 1
fi

exit 0
```

保存退出后，修改脚本的执行权限

chmod +x .git/hooks/pre-commit















