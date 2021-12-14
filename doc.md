## **接口说明**
### 请求通用

1.header

|  名称   | 说明  | 示例 | 是否必要 |  备注 |
|  ----  | ----  | ---- | ---- | ---- |
| Auth-Token  | 登录后的服务器端生成的token | 16/32位字符串 | 否（没有该值视为未登录） |
| Cu-Language  | 语言代码 | zh-cn | 否（默认为英文？) |
| channel | 渠道代码 | google/baidu/normal等 | 否 | 用来标识安装包来源(打包时生成)
| User-Agent | 包、手机信息等 | xq/1.0/ android/28/ (huawei mate40pro) | 否 | 马甲包别名/版本号 手机平台/系统版本 （手机型号） |
| Client-Id | 手机唯一标识 | | 否 | |

2.翻页参数

|  名称   | 说明  | 数据格式 |
| ---- | ---- | ---- |
| page | 翻页页码 | str |
| limit | 翻页条目数量 | int |


### 返回值通用  
_http code 按照标准返回即可，例如成功 200、201 ，失败 500，找不到接口404等_

1.header  

|  名称   | 说明  | 示例 | 是否必要 |  备注 |
|  ----  | ----  | ---- | ---- | ---- |
| Auth-Token | 登录、更换token时返回 | 16/32位字符串 | 客户端登录时必要 |

2.通用返回格式

```json
  {
      "code": 200, //正确、错误代码
      "message":"success",//提示信息
      "data":{} //实际返回值  部分接口为[] 请参考实际每个接口返回值部分
  }
```

3.带翻页的返回格式
```json
  {
      "code": 200, //正确、错误代码
      "message":"success",//提示信息
      "data":{
        "page":"下一页页码 可以为string类型",
        ......
      }
  }
```

3.错误代码  

_由于之前接口设计，很多错误码都是根据单个接口设计，因此返回码请参考每个接口的单独说明。_

|  代码  |  说明  | data  |  备注  |
|  ----  | ----  | ----  |  ----  |
| 1007 | 正常请求返回 |  |
| 1000/1001 | 登录过时/token错误 |  | 客户端收到此代码强制注销 |
| 999 | app过期需要升级 | ```{"redirect_url" : "apk更新地址"} ```|
| 1006 | 无更多数据 |  | 翻页无数据、首次请求无数据 |
| 1017 | 用户被拉黑 |  |  通用中的message为拉黑原因  |
| 500 | 常规、未知错误 |  通用中的message为错误提示  |

### 接口设计  
_接口均为原后台人员设计，如有疑问，可联系我进行沟通、修改。_  
_由于接口均为反推，所以顺序并非开发顺序_

1.splash页面  
>获取全局配置  
用来获取app的全局配置文件
+ 地址 v1/configs/global
+ 请求方式：get
+ 参数 无
+ 返回值

```json
  {
    "private_chat_price":"220",//解锁私聊价格
    "unlock_wechat_price":"990",//解锁微信价格 （这里可以修改，原来的后台执意把价格显示配置再这里,再本项目中标识解锁whatsapp号码)
    "file_domain":"",//文件服务器域名（这里图片有专门的服务器，可以修改）
    "netease_kf":"客服的云信ID",
    "system_helper":"推送云信消息的ID",//产品日常推送的消息，需要处理逻辑的使用云信自定义消息实现
    "user_protocol":"用户协议",//用户协议是网页编写，值为html地址
    "user_privacy":"隐私政策地址",
    "withdraw_url":"提现网页地址",
    "is_open_invite":"0|1",//邀请入口是否开启
    "withdraw_invite_url":"",//邀请提现的地址
    "album_extension":{
      "image_duration":"3",//阅后即焚时间 单位秒
      "member_image_duration":"10",//会员阅后即焚时间  单位秒
      "video_amount":"50",//视频价格 单位钻石
    },
    "invite_web_url":"邀请入口地址",
    "chat_allow_jump_url":["http://www.baidu.com","http://www.google.com"]//私聊可跳转地址
  }
```

2.登录
>发送验证码  
+ 地址 v1/sms
+ 请求方式：post
+ 参数
|  名称   | 请求方式  | 数据格式 | 说明  |
| ---- | ---- | ---- | ---- |
| area | post | str |  区域编号,如中国为+86  |
| mobile | post | str |  手机号码  |
| type | get | str | login\|password 区分是找回密码还是登录(这里第一版先把找回密码去掉)|
+ 请求示例
```json
  //post  v1/sms?type=login
  {
    "area":"86",
    "mobile":"18500038484"
  }
```
+ 返回值
```
  {
    "code":1007,
    "message":"success"
  }
```

>登录
+ 地址 v1/sms
+ 请求方式：post
+ 参数
|  名称   | 请求方式  | 数据格式 | 说明  |
| ---- | ---- | ---- | ---- |
| type | get | str |  mobile_sms\|email 第一版有mobile_sms即可，为手机号验证 |
| mobile | post | str |  手机号码  |
| code | post | str | 验证码 |
| area | post | str | 国家编码 |
+ 请求示例
```json
  //post  v2/auth?type=mobile_sms
  {
    "area":"86",
    "mobile":"18500038484",
    "code":"12345"
  }
```
+ 返回值
```json
  {
  "uuid":"用户唯一标识",
  "nickname":"用户昵称",
  "gender":"1|2",//1男2女
  "avatar":"",//用户头像
  "member":{
    "status":"true|false" //是否购买了会员
  },
  "auth_user":{
    "status":"true|false" //是否认证过
  },
  "age":18, //年龄
  "user_detail":{
    "intro":"个人签名",
    "region":"所在地区",
    "height":"身高",
    "weight":"体重",
    "reg_schedule":"100|101|102|103",
    //****这里需要特别注意，这里联动了注册所在步骤，
    //主要是避免用户注册到一半杀掉app，重新注册时返回至之前杀掉的页面
    //100：完成注册
    //101：未选择性别
    //102：未填写头像、昵称、生日
    //103：未选择想要的关系
    //第一次注册返回101
    //这里我们只用100，101，102即可，103暂时删除
  },
  "photo":{
    "type":"image|video",
    "resource":"资源地址",
    "width":"图片宽度",
    "height":"视频宽度",
    "preview":"图片预览图",
    "uuid":"资源唯一标识",
    "cover":"加了高斯模糊的图片",//这里应该是只有阅后即焚的资源才需要
    "pay_type":"red_packet|fire|free",//red_packet：收费视频，fire：阅后即焚图片，free：免费
    "status":"100|101",//阅后即焚状态 100未读，101已读 这里注意阅后即焚每天重置一次
    "small_cover":"加了告诉模糊的预览图",
    }
    "follow_count":"0",//我关注了多少人
    "followed_count":"0",//多少人关注了我
    "birthday":"1990-03-19",//生日
    "wechat":{
      "number":"微信号",
      "status":"pass",//本项目中始终返回pass即可
      "lock":"true|false",//是否隐藏了号码
    },
    "is_register":"0|1",//是否是第一次注册的用户
    "has_video":"true|false",//是否上传了视频  视频只有女生可以上传
    "qa":{
      "url":"在线客服地址",//这里我们之前接了第三方客服系统，是个web版本的im，没有可以不用管
    },
    "account_state":"normal|destroying|destroyed",//始终返回normal即可，我们暂时不做销毁功能
    "UserInfoDetailExtra":{
      //以下字段都是一些用户选择完的标签
      "emotion":{//情感状态标签
        "uuid":"唯一标识",
        "name":"名称"
      },
      "child":{//是否有孩子
        "uuid":"唯一标识",
        "name":"名称"
      },
      "education":{//学历
        "uuid":"唯一标识",
        "name":"名称"
      },
      "child":{//是否有孩子
        "uuid":"唯一标识",
        "name":"名称"
      },
      "income":{//收入
        "uuid":"唯一标识",
        "name":"名称"
      },
      "smoke":{//是否吸烟
        "uuid":"唯一标识",
        "name":"名称"
      },
      "drink":{//是否喝酒
        "uuid":"唯一标识",
        "name":"名称"
      },
    },
    //兴趣爱好，这是个多选标签
    "hobbys":[
      {
        "uuid":"唯一标识",
        "name":"抽烟"
      },
      {
        "uuid":"唯一标识",
        "name":"喝酒"
      },
      {
        "uuid":"唯一标识",
        "name":"烫头"
      }
    ],
    "invite_info":{
      "invite_qrcode_url":"邀请码二维码地址"
    },
    "photo_count":"0",//用户上传的照片数量，不包含头像
}
```

3.注册
>选择性别
+ 地址 v1/accounts/{uuid}
+ 请求方式：put
+ 参数
|  名称   | 请求方式  | 数据格式 | 说明  |
| ---- | ---- | ---- | ---- |
| uuid | path | str |  |
| gender | post | int |  1\|2  |
+ 请求示例
```json
  //put v1/accounts/用户uuid
  {
    "gender":"1|2"
  }
  //用户选择完性别后，userinfo.user_detail.reg_schedule值需要更新到102
```

>填写基本资料
+ 地址 v1/accounts/{uuid}
+ 请求方式：put
+ 参数
|  名称   | 请求方式  | 数据格式 | 说明  |
| ---- | ---- | ---- | ---- |
| uuid | path | str |  |
|avatar | post | str| 头像 |
| nickname | post | str |  昵称  |
| birthday | post | str | 生日 |
+ 请求示例
```json
  //put v1/accounts/用户uuid
  {
    "avatar":"头像地址",//上传图片请参考上传图片文档
    "nickname":"昵称",
    "birthday":"2012-02-02"
  }
  //用户填写完基本信息后，userinfo.user_detail.reg_schedule值需要更新到100
```
