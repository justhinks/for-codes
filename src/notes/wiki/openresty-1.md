## openresty 替代 nginx

1、确定线上nginx的编译参数，通过nginx -V，得到如下信息：
nginx version: nginx/1.10.3 (Ubuntu)
built with OpenSSL 1.0.2g  1 Mar 2016
TLS SNI support enabled
configure arguments: --with-cc-opt='-g -O2 -fPIE -fstack-protector-strong -Wformat -Werror=format-security -Wdate-time -D_FORTIFY_SOURCE=2' --with-ld-opt='-Wl,-Bsymbolic-functions -fPIE -pie -Wl,-z,relro -Wl,-z,now' --prefix=/usr/share/nginx --conf-path=/etc/nginx/nginx.conf --http-log-path=/var/log/nginx/access.log --error-log-path=/var/log/nginx/error.log --lock-path=/var/lock/nginx.lock --pid-path=/run/nginx.pid --http-client-body-temp-path=/var/lib/nginx/body --http-fastcgi-temp-path=/var/lib/nginx/fastcgi --http-proxy-temp-path=/var/lib/nginx/proxy --http-scgi-temp-path=/var/lib/nginx/scgi --http-uwsgi-temp-path=/var/lib/nginx/uwsgi --with-debug --with-pcre-jit --with-ipv6 --with-http_ssl_module --with-http_stub_status_module --with-http_realip_module --with-http_auth_request_module --with-http_addition_module --with-http_dav_module --with-http_geoip_module --with-http_gunzip_module --with-http_gzip_static_module --with-http_image_filter_module --with-http_v2_module --with-http_sub_module --with-http_xslt_module --with-stream --with-stream_ssl_module --with-mail --with-mail_ssl_module --with-threads


2、下载openresty源代码，在源代码页面（ https://openresty.org/en/download.html ） 可以选择具体的源代码版本
如上所示，我们选择最新的版本1.21.4.1进行安装（ https://openresty.org/download/openresty-1.21.4.1.tar.gz ）。假设我们目前所在的目录为/root目录，则执行如下操作:
//下载最新版本
wget https://openresty.org/download/openresty-1.21.4.1.tar.gz

//解压
tar -zxvf openresty-1.21.4.1

//进入到源代码目录
cd openresty-1.21.4.1


3、执行configure操作，具体操作如下：
3.1 安装依赖库
apt-get install libxml2-dev libxslt-dev libgd-dev libgeoip-dev
3.2 执行./configure命令
./configure --conf-path=/etc/nginx/nginx.conf --http-log-path=/var/log/nginx/access.log --error-log-path=/var/log/nginx/error.log --lock-path=/var/lock/nginx.lock --pid-path=/run/nginx.pid --http-client-body-temp-path=/var/lib/nginx/body --http-fastcgi-temp-path=/var/lib/nginx/fastcgi --http-proxy-temp-path=/var/lib/nginx/proxy --http-scgi-temp-path=/var/lib/nginx/scgi --http-uwsgi-temp-path=/var/lib/nginx/uwsgi --with-debug --with-pcre-jit --with-ipv6 --with-http_ssl_module --with-http_stub_status_module --with-http_realip_module --with-http_auth_request_module --with-http_addition_module --with-http_dav_module --with-http_geoip_module --with-http_gunzip_module --with-http_gzip_static_module --with-http_image_filter_module --with-http_v2_module --with-http_sub_module --with-http_xslt_module --with-stream --with-stream_ssl_module --with-mail --with-mail_ssl_module --with-stream_ssl_preread_module --with-http_secure_link_module --with-http_random_index_module --with-threads
备注：直接复制上述命令执行即可 ，因为还是跟第一步nginx的编译参数有不同之处，不同点：
- openresty的安装在/usr/local/openresty中，
- nginx.conf配置文件路径不同：
openresty自带的nginx.conf配置文件路径在 /usr/local/openresty/nginx/conf/nginx.conf
原本的nginx自带的配置文件路径在 /etc/nginx/nginx.conf
这个过程中如果出现错误，很可能依赖的库没有安装（不过这种情况出现的几率较少，因为已经成功安装了nginx，其起来的库理论上来说应该在系统中了，如果出现错误，则需要具体错误具体分析）
configure报错：
1./configure: error: the HTTP XSLT module requires the libxml2/libxslt libraries. You can either do not enable the module or install the libraries.
解决方法： apt-get install libxml2-dev libxslt-dev
2./configure: error: the HTTP image filter module requires the GD library. You can either do not enable the module or install the libraries.
解决方法： apt-get install libgd-dev
3./configure: error: the GeoIP module requires the GeoIP library.
You can either do not enable the module or install the library.
解决方法： apt-get install libgeoip-dev
Configuration summary
  + using threads
  + using system PCRE library
  + using system OpenSSL library
  + using system zlib library

  nginx path prefix: "/usr/local/openresty/nginx"
  nginx binary file: "/usr/local/openresty/nginx/sbin/nginx"
  nginx modules path: "/usr/local/openresty/nginx/modules"
  nginx configuration prefix: "/etc/nginx"
  nginx configuration file: "/etc/nginx/nginx.conf"
  nginx pid file: "/run/nginx.pid"
  nginx error log file: "/var/log/nginx/error.log"
  nginx http access log file: "/var/log/nginx/access.log"
  nginx http client request body temporary files: "/var/lib/nginx/body"
  nginx http proxy temporary files: "/var/lib/nginx/proxy"
  nginx http fastcgi temporary files: "/var/lib/nginx/fastcgi"
  nginx http uwsgi temporary files: "/var/lib/nginx/uwsgi"
  nginx http scgi temporary files: "/var/lib/nginx/scgi"

./configure: warning: the "--with-ipv6" option is deprecated
cd ../..
4、第三步执行没有如何错误的情况下，执行如下操作:
4.1 执行make
make  //如果出现错误，则需要分析原因
4.2 进入到nginx的Makefile所在目录
//这里假设仍然处在执行make命令的目录下
cd build/nginx-1.21.4/objs
4.3 编辑Makefile文件
//编辑Makefile文件
vim Makefile
4.3 找到install命令所在位置,可以通过 /install 查找，结果如下：
[图片]
4.4 注释掉以下内容，在对应的行前面加 # 号即可注释：
//下面这三句话不注释掉也没事，这里koi-win、koi-utf、win-utf替换掉也没事，内容完全一样
#cp conf/koi-win '$(DESTDIR)/etc/nginx'
#cp conf/koi-utf '$(DESTDIR)/etc/nginx'
#cp conf/win-utf '$(DESTDIR)/etc/nginx'

//为了保持/etc/nginx/配置目录的整洁性，这些xxx.default的文件不需要拷贝过去
//这些文件拷贝过去其实也不影响，因为我们没有用到xxx.default这些配置
#cp conf/mime.types '$(DESTDIR)/etc/nginx/mime.types.default'
#cp conf/fastcgi_params \
#                '$(DESTDIR)/etc/nginx/fastcgi_params.default'
#cp conf/fastcgi.conf '$(DESTDIR)/etc/nginx/fastcgi.conf.default'
#cp conf/uwsgi_params \
#                '$(DESTDIR)/etc/nginx/uwsgi_params.default'
#cp conf/scgi_params \
#                '$(DESTDIR)/etc/nginx/scgi_params.default'
#cp conf/nginx.conf '$(DESTDIR)/etc/nginx/nginx.conf.default'                
4.5 保存退出
4.6 切换到openresty-1.21.4源码目录，假设在` /root/openresty-1.21.4 `
cd /root/openresty-1.21.4
4.7 执行 make install
make install 成功后，会出现如下信息：
mkdir -p /usr/local/openresty/site/lualib /usr/local/openresty/site/pod /usr/local/openresty/site/manifest
ln -sf /usr/local/openresty/nginx/sbin/nginx /usr/local/openresty/bin/openresty
5、确保原有的nginx的运行是以绝对路径运行的（具体原因详见步骤9解释）。
执行如下命令：
ps -ef | grep nginx | grep master
结果如下：
[图片]
如上图所示：
- master进程的二进制文件路径为 /usr/share/nginx/sbin/nginx
- 运行的命令是通过绝对路径运行 /usr/share/nginx/sbin/nginx -c /etc/nginx/nginx.conf
如果启动nginx的命令的二进制文件路径不是 /usr/share/nginx/sbin/nginx ，那么后面步骤的命令就需要替换成自己系统中实际的二进制文件路径。
重要：目前预发环境启动的nginx的二进制文件路径目录为 /usr/sbin/nginx，后面的步骤会针对这两种路径都列出操作命令。
备注：提供另外一种获取master进程启动的命令行，具体操作如下：
//获取nginx master进程的pid，（在我本地下面命令输出的pid为28969）
ps -ef | grep nginx | grep master | grep -v grep | awk '{print $2}'

//下面命令中的28969为第一步输出的pid
cat /proc/28969/cmdline | xargs echo
下面对我本地操作的结果进行截图，作为参考
[图片]
6、备份原有nginx二进制文件
我们选择在同样的目录下备份：
6.1 线上启动的二进制文件路径为 /usr/sbin/nginx ，则执行以下命令：
cp /usr/sbin/nginx /usr/sbin/nginx.bak
6. 2 线上启动的二进制文件路径为 /usr/share/nginx/sbin/nginx ，则执行以下命令：
cp /usr/share/nginx/sbin/nginx /usr/share/nginx/sbin/nginx.bak
7、确认原有nginx的pid文件存放路径
通过第一步nginx的编译参数--pid-path=/run/nginx.pid可知，存放pid文件的路径为/run/nginx.pid，可以通过对比nginx master进程的pid和/run/nginx.pid文件的内容 ， 确保其对应的pid文件路径是否正确，步骤如下：
//获取nginx master进程的pid，（在我本地下面命令输出的pid为1598）
ps -ef | grep nginx | grep master | grep -v grep | awk '{print $2}'

//对比下面命令输出的pid是否和上面命令输出的pid一致
//如果不一致，则极有可能是在运行nginx的通过参数额外制定了pid文件路径，
//如果对比确认不一致的话，就暂停操作，需要再次细化操作
cat /run/nginx.pid
平滑升级的关键步骤：
8、用openresty自带的nginx，替换掉原来的nginx二进制文件
8.1 线上启动的二进制文件路径为 /usr/sbin/nginx ，则执行以下命令：
cp -f /usr/local/openresty/nginx/sbin/nginx /usr/sbin/nginx
8.2 线上启动的二进制文件路径为 /usr/share/nginx/sbin/nginx ，则执行以下命令：
cp -f /usr/local/openresty/nginx/sbin/nginx /usr/share/nginx/sbin/nginx
9、执行下述命令
//假设已经通过第七步确认原有nginx的pid文件路径为/run/nginx.pid
kill -USR2 `cat /run/nginx.pid`
上述命令的作用：
给nginx发送USR2信号后，nginx会将/run/nginx.pid文件重命名为/run/nginx.pid.oldbin，然后用新的可执行文件启动一个新的nginx主进程和对应的工作进程，并新建一个新的nginx.pid保存新的主进程号
本地实验结果如下，供参考：
root@2phdsgsioufa6pei:/# kill -USR2 `cat /run/nginx.pid` 
root@2phdsgsioufa6pei:/# cd /run
root@2phdsgsioufa6pei:/run# ls -l nginx.pid nginx.pid.oldbin 
-rw-r--r-- 1 root root 6 May  4 19:21 nginx.pid
-rw-r--r-- 1 root root 6 May  4 18:31 nginx.pid.oldbin
root@2phdsgsioufa6pei:/run# cat nginx.pid //新的主进程id
25209
root@2phdsgsioufa6pei:/run# cat nginx.pid.oldbin //老的主进程id
28969
此时通过查看nginx进程，就会发现新老master进程和worker进程都存在，如下图所示：
[图片]
9.1 这一步仍然需要观察日志（日志目录/var/log/nginx），如果发现有问题，则可以通过下面命令进行回滚 ：
//优雅的关闭新worker进程
kill -WINCH `cat /run/nginx.pid`

//优雅的关闭新master进程
kill -QUIT `cat /run/nginx.pid`

//恢复原有nginx二进制文件
//情况1，线上启动的二进制文件路径为/usr/sbin/nginx，则执行下面命令
mv -f /usr/sbin/nginx.bak /usr/sbin/nginx

//情况2，线上启动的二进制文件路径为/usr/share/nginx/sbin，则执行下面命令
mv -f /usr/share/nginx/sbin/nginx.bak /usr/share/nginx/sbin/nginx
遇到的坑：
在本地实验的时候，最开始安装nginx后，把nginx的可执行文件路径加入到PATH变量中，即通过
export PATH=/usr/share/nginx/sbin:$PATH
然后启动nginx的时候，直接通过如下命令启动：
nginx -c /etc/nginx/nginx.conf
这种方式启动的nginx，在执行这里第九步 kill -USR2 `cat /run/nginx.pid` 的时候，没有任何响应，不会生成 /run/nginx.pid.oldbin 文件，通过观察 /var/log/nginx/error.log 日志，发现出现如下报错:
2023/05/04 18:14:42 [alert] 19644#19644: execve() failed while executing new binary process "nginx" (2: No such file or directory)
最后通过搜索找到问题原因： nginx启动没有用绝对路径去启动，而是依赖了PATH 。
详情参考： https://groups.google.com/g/openresty/c/HiV3c-JwTZ4
[图片]
问：上周的实验步骤中为什么没有出现这个错误？
答：上周的实验步骤中，采用的本地环境是ubuntu18.04和nginx-1.18，其中nginx是通过lnmp包安装的，直接用的是service启动的方式，采用的是绝对路径形式，故没有出现此问题。
所以，我们一定要确保nginx的启动是使用绝对路径启动的
10、给旧的主进程号发送WINCH信号
kill -WINCH `cat /run/nginx.pid.oldbin`
旧的主进程号 在 /run/nginx.pid.oldbin 里面
上述命令的作用：
旧的主进程号收到WINCH信号后，将旧进程号管理的旧的工作进程优雅的关闭。即一段时间后旧的工作进程全部关闭，只有新的工作进程在处理请求连接。这时，依然可以恢复到旧的进程服务，因为旧的进程的监听socket还未停止。
本地实验结果如下，供参考：
[图片]
特别注意：在已经执行了kill -WINCH `cat /run/nginx.pid.oldbin`了这个步骤后，需要长时间观察nginx日志， 日志路径在/var/log/nginx/ ，通过观察是否有错误发生。
//查看access.log tail -f /var/log/nginx/access.log //查看error.log tail -f /var/log/nginx/error.log
这个灰度过程建议持续1-2天：
- 业务访问没有任何问题后，再执行步骤11
- 如果发现有问题，则执行步骤10.1，进行回滚
10.1 发现问题，想恢复到老的nginx，可以通过以下步骤：
1）给旧的主进程号发送HUP命令
kill -HUP `cat /run/nginx.pid.oldbin`
上述命令表示： 给旧的主进程号发送HUP命令，此时nginx不重新读取配置文件的情况下重新启动旧主进程的工作进程
2）优雅的关闭新的主进程
kill -QUIT `cat /run/nginx.pid`
3）恢复原有nginx二进制文件
//情况1，线上启动的二进制文件路径为/usr/sbin/nginx，则执行下面命令
mv -f /usr/sbin/nginx.bak /usr/sbin/nginx
//情况2，线上启动的二进制文件路径为/usr/share/nginx/sbin，则执行下面命令
mv -f /usr/share/nginx/sbin/nginx.bak /usr/share/nginx/sbin/nginx
11、确保用openresty替换原有的的nginx没有问题后，优雅的关闭旧的主进程
kill -QUIT `cat /run/nginx.pid.oldbin`
给旧的主进程发送QUIT信号后，旧的主进程退出，并移除logs/nginx.pid.oldbin文件，openresty替换nginx的工作完成。
本地实验结果如下，供参考：
[图片]
1.启动命令是
/usr/sbin/nginx -g daemon on
/usr/sbin/nginx -c /etc/nginx/nginx.conf

2.切换二进制文件后，/usr/sbin/nginx 如果不-c指定配置文件位置，默认的将是openresty的配置文件。

3.由于2，导致openresty并没有真正使用到原来nginx的配置文件

