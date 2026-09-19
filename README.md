# Modules Log

Pin 日志模块，提供统一的业务日志管理能力。

## 日志类型

模块提供以下日志类型：

- [操作日志](https://ipiner.cn/packages/log/operation)：记录用户对业务资源的操作行为。
- [行为日志](https://ipiner.cn/packages/log/activity)：记录用户或系统产生的业务行为事件。
- [登录日志](https://ipiner.cn/packages/log/login)：记录用户登录事件。

## 写入日志

```php
use Pin\Modules\Log\Facades\Log;
use Pin\Modules\Log\Payloads\ActivityPayload;
use Pin\Modules\Log\Payloads\LoginPayload;

Log::create(new LoginPayload($user));
Log::create(new LoginPayload(null, 10010, '用户不存在'));

Log::create(
    new ActivityPayload('order.submitted')
        ->subject($order->id, $order->name, 'order')
        ->title('提交订单')
        ->context(['source' => 'web'])
);
```

`ActivityPayload` 的第二个参数可以指定对象、标题等属性；显式属性覆盖事件默认值。
行为枚举可实现 `IActivityEvent` 并使用 `ActivityEvent` trait，枚举值格式为
`event|title` 或 `event|title|subject_type`。省略对象类型时使用事件名称第一个点之前的部分。
登录用户为空时记录 `uid = 0`、`username = ''`，登录日志保留载荷中的 `created_at`。

## 自动操作日志

模型使用 `Models\Concerns\HasOperationLog`，并在 `config/pin/modules/log.php` 中配置对象名称字段：

```php
'operation' => [
    'subject_name_columns' => [
        'orders' => 'name',
        'users' => ['realname', 'username'],
    ],
],
```

也可以覆盖模型的 `subjectNameColumn()`、`subjectType()`、`ignoredOperationAttributes()`、
`transformOperationValue()` 和 `newOperationPayload()`。

- 默认忽略 `created_at`、`updated_at`，仅更新时间戳时不写操作日志。
- 更新日志仅转换实际变更字段及其旧值；强制删除由 `force-deleted` 事件记录，避免重复写入普通删除日志。
- `withoutOperationLogging()` 临时禁用当前模型类的记录，支持嵌套调用，退出或抛异常后恢复原状态。
- `mergeOperationChanges($old, $new)` 只合并 `$new` 提供的字段，忽略未变化值；数组字段整体替换，多次合并保留最初旧值与最新值。删除某个值时请在 `$new` 中显式传入 `null`。
- 模型事件中的日志写入失败会记录警告，不中断业务模型操作；直接调用 `Log::create()` 或手动合并时的写入异常由调用方处理。

## 查询与扩展

配置通过 `pin-modules-log-config` 标签发布，迁移通过 `pin-modules-log-migrations` 标签发布。
每类日志可独立指定 `model`、`controller`、`route_enabled` 和 `route_name`，公共路由前缀和中间件位于 `routes`。
`Log` 服务支持类名注入，保留 `pin.modules.log` 容器名称和原有 Facade。

`LogService::options($columns, $map)` 缓存去重查询后经回调转换的筛选项结果，默认有效期一天。
缓存键保持为 `{table}.options`，每张表共用一份结果；命中缓存时直接返回，不重复查询或执行映射回调。
`activityOptions()` 统一生成行为、操作日志的事件和对象类型筛选项。
缓存有效期可通过继承服务并覆盖 `$optionsTTL` 调整。

需要刷新筛选项时，可调用 `Cache::forget($model->getTable().'.options')`。

## 验证

在包目录安装依赖后运行：

```sh
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint --test
```

## 文档

[https://ipiner.cn/packages/log](https://ipiner.cn/packages/log)
