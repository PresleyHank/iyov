#IYOV-A web proxy and analyze tool especially for app engineers.

### How it comes
Sometimes we\`ll help colleagues to locate issues between app and servers. I have to login each server and dump the tcp packages, 
but it\`s not that humanzied, or many ads. So I build this tool. It\`s free and open to all developers. You can know every details 
of each `HTTP` request and response packages. Now it\`s avaliable in the [IYOV](http://iyov.io) cloud platform, and I`m still working on 
making it more easy to use.


### Features
* A proxy for http and https;
* Support `Content-Length`;
* Support `Transfer-Encoding: chunkded`;
* Support `Content-Encoding: gzip`;
Example:
 ![request](/Doc/Image/request.png)
 ![response](/Doc/Image/response.png)


### Install
* clone this repository
* add `test.iyov.io ＝> 127.0.0.1` to your own hosts;
* get into the root dir and run `php start.php start` command;

### How to use
**If using iyov in your computer, app and computer should connect to the same network,otherwise it\`s workless.**

1. set proxy config, `Address: your computer local address`, `Port: 9733`
2. open `test.iyov.io:8080` in browser

Ok, Now you can surf the internet or test applications in mobile devices, every detail will be explosed in the tab.
