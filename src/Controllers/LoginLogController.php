<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Controllers;

use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Override;
use Pin\Errors\Errors;
use Pin\Http\ApiResponse;
use Pin\Modules\Log\Events\LogEvent;
use Pin\Modules\Log\Models\LoginLog;
use Pin\Pagination\Pagination;
use Pin\Scramble\SelectOption;
use Pin\Validation\QueryableRules as Queryable;

/**
 * 查询登录日志和登录结果筛选项。
 */
#[Group('系统 / 日志')]
class LoginLogController extends Controller
{
    /**
     * 登录日志
     *
     * @return ApiResponse<Pagination<LoginLog>>
     */
    public function index(Request $request): ApiResponse
    {
        $rules = [
            ...$this->service->baseRules(),
            // 登录返回信息
            'message' => Queryable::like(),

            // 登录返回码
            'code' => Queryable::inNumeric(),
        ];
        $request->validate($rules);

        return $this->success($this->service->pagination($rules));
    }

    /**
     * 登录日志返回码筛选项
     *
     * @return ApiResponse<SelectOption[]>
     */
    public function options(): ApiResponse
    {
        $data = $this->service->options('code', function (Collection $data) {
            return $data->sort()
                ->values()
                ->map(fn ($item) => [
                    'label' => $item->code.'/'.($item->code === 0 ? '登录成功' : Errors::get($item->code)->message()),
                    'value' => $item->code,
                ])
                ->toArray();
        });

        return $this->success($data);
    }

    #[Override]
    protected function modelClass(): string
    {
        return LogEvent::Login->config()['model'];
    }
}
