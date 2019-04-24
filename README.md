# swoole-docker-api
support for [HTTP connection hijacking](https://docs.docker.com/engine/reference/api/docker_remote_api_v1.22/#3-2-hijacking), send http request by swoole tcp client.
## branch
### write-parse
write http response parse by myself

## notes

### structure
```
POST /info HTTP/1.1\r\n
Accept: application/json\r\n
Content-Type: application/json\r\n
Host: 47.92.249.208\r\n
Connection: Keep-Alive\r\n\r\n
{"go":true}\r\n\r\n
```
tips: https://stackoverflow.com/questions/26811822/is-it-necessary-to-check-both-r-n-r-n-and-n-n-as-http-header-content-separ
### response chunk
https://my.oschina.net/ososchina/blog/666761


## reference
https://github.com/amphp/artax/blob/f9aedd0e84620ff228d2e4f3ccd5d18416ba18ab/lib/DefaultClient.php
https://github.com/amphp/artax/blob/fc06d5798d675818fdfdffdba1e378c3c3687944/lib/Internal/Parser.php
