#iyov

> HTTP 代理以及抓包工具

* Gateway 对外websocket服务
* HttpProxy Http代理服务类，以及发送统计数据到Gateway进程，`UDP`协议
* Proxy 代理基础类
* Lib/Http Http 工具类
* Lib/String 字符串工具类

### 目前已经实现可以Http代理和抓包但是存在以下问题
1. 刷新页面可能只有response数据;
2. UDP数据可能过长;
3. 接受到客户端完整请求后再代理服务再请求目标服务器，存在性能不优情况;
4. 代理服务多进程支持;
5. 代码冗余，比如统计函数可以放到Http工具类里面;

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