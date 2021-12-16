
### 文件目录说明
- /usr/local/openresty/lualib
    - forward_agent.lua
    - resp_encrypt.lua

-  /usr/local/openresty/lualib/libs 
    - verify.lua
    
- /usr/local/openresty/nginx/conf/sites-enabled
    - api.conf
    
### 修改 verify.lua 参数
```text
return _M:new("SALT", "KEY",
    { host = "REDIS host", port = 6379, password = "REDIS密码" })
```

