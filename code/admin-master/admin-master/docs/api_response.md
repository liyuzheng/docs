## 关于apiResponse返回说明
### 业务正常,但是没有数据(比如: 我喜欢的人的列表)
```php
# 有分页
return api_rr()->getOKnotFoundResultPaging('你没有黑名单哟');
# 无分页
return api_rr()->getOKnotFoundResult('你没有黑名单哟');
```

### 客户端传过来的数据,在我们这边查不到的
```php
return api_rr()->notFoundResult();
```

### 操作被禁止,客户端弹出提示
```php
return api_rr()->forbidCommon('请先发送验证码或者验证码错误');
```
