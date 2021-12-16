---
--- Generated by EmmyLua(https://github.com/EmmyLua)
--- Created by riley.
--- DateTime: 2020/11/23 8:43 下午
---

local verify = require("libs.verify")

if ngx.ctx.need_encrypt then
    local chunk, eof = ngx.arg[1], ngx.arg[2]
    if ngx.ctx.buffered == nil then ngx.ctx.buffered = {} end

    if chunk ~= "" and not ngx.is_subrequest then
        table.insert(ngx.ctx.buffered, chunk)
        ngx.arg[1] = nil
    end

    if eof then
        local body = verify:encrypt(table.concat(ngx.ctx.buffered))
        ngx.ctx.buffered = nil
        ngx.arg[1] = body
    end
end
