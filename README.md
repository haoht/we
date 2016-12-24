####志愿者

 > 一个为公益服务的平台，在此你可参与公益活动，结识小伙伴，讨论话题。伸出你的援助之手，行动起来，让社会变得更美好
 >  [在线地址](http://huizhoustu.com/haiku/2016Sise/index.php)

####用到的技术
* 团队使用git协作
*  前台AMD（requirejs）异步加载模块，sass预处理器，bootstrap+jquery
*  后台thinkphp+Rbac角色控制

---

####[后台管理](http://huizhoustu.com/haiku/2016Sise/admin/index.php)
* 账号admin1 密码111111

---

####模块介绍
* 活动模块
1. 可根据活动性质，活动分类，活动状态，活动时间和搜索活动名称来找到要参与的活动

2. 活动详情页面可查看活动的详细信息，参与活动者可详情信息下面提问，组织用户可在后台看到活动者的提问做出回答

3. 参与者可报名参加活动，需提交个人基本信息（姓名，电话，QQ，身份证）后，待组织用户后台核实后即可参与活动

* 小组模块
1. 组织用户创建小组需要通过管理员的审核（避免产生垃圾信息）

2. 用户可以在这里分享各种话题，加入小组后可以探讨各种话题

* 话题模块
1. 小组里面有若干个话题，组织用户和志愿者都可以发布话题，（1：N）


2. 在话题中可以发表各种评论，各志愿者可以在此评论自己的观点，交流自己的看法。

* 个人中心模块
1. 可在此管理自己的个人信息，可查看自己加入的小组，话题。查看自己发表过的话题，评论等等

---

####后端架构

* 用户组
1. 普通用户（即活动参与者）需通过邮箱验证
2. 组织用户（即发表活动的组织）注册需管理员后台审核身份并通过邮箱验证
3. 管理员
通过thinkphp的Rbac权限控制，控制每个角色

* 管理员
1. 用户管理（审核组织用户注册，组织用户申请小组审核）
2. 各块内容管理
3. 菜单管理
4. 各配置项管理
5. 角色管理

* 组织用户
1. 管理自己创建的小组，活动
2. 普通用户报名活动后，组织用户需后台审核验证信息





