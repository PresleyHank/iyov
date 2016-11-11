#IYOV-A web proxy and analyze tool especially for app engineers.

### How it comes
Sometimes we\`ll help colleagues to locate issues between app and servers. I have to login each server and dump the tcp packages, 
but it\`s not that humanzied, or much ads. So I build this tool. It\`s free and open to all developers. You can know every details 
of each `HTTP` request and response packages.


### Fitures
* A proxy for http and https;
* Support `Content-Length`;
* Support `Transfer-Encoding: chunkded`;
* Support `Content-Encoding: gzip`;



### Install
* clone this repository
* add `iyov.io ＝> 127.0.0.1` to your own hosts;
* get into the root dir and run `php start.php start` command;

### How to use
**If using iyov in your computer, app and computer should connect to the same network,otherwise it\`s workless.**

1. set proxy config, `Address: computer local address`, `Port: 9733`
2. open `iyov.io:8080` in browser

Ok, Now you can surf the internet or test applications in mobile devices, every detail will be explosed in the tab.