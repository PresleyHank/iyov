#iyov

> HTTP 代理以及抓包工具

* Gateway 对外websocket服务
* HttpProxy Http代理服务类，以及发送统计数据到Gateway进程，`TCP`协议
* Proxy 代理基础类
* Lib/Http Http 工具类
* Lib/String 字符串工具类

### FIXED & IMPROVED
* 重构HttpProxy;
* Response body 转码为编码UTF-8;
* 统计数据上报改为TCP协议;
* 统计数据结构可能造成信息被覆盖BUG;
* 客户端第一个HTTP包可能没处理BUG;
* 支持`Transfer-Encoding`字段;
* 支持代理服务多进程；
* 支持`Contetn-Encoding: gzip, inflate` 字段

### 安装
* 克隆仓库，并把根目录重命名为Applications(Workerman 服务要求的，启动脚本只加载Applications下的启动文件)；
* 在Applications同级目录下克隆Workerman源码；
* 配置本地`hosts`：`iyov.io ＝> 127.0.0.1`;

### 使用
#### 移动端
> 打开HTTP代理，地址为本机`9733`端口
#### PC端
> 打开本地HTTP代理 
## 浏览器地址：iyov.io:8080/iyov.html ##