<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Controllers;

use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Override;
use Pin\Http\ApiResponse;
use Pin\Modules\Log\Events\LogEvent;
use Pin\Modules\Log\Events\OperationEvent;
use Pin\Modules\Log\Models\OperationLog;
use Pin\Pagination\Pagination;
use Pin\Scramble\SelectOption;

/**
 * 操作日志接口控制器
 */
#[Group('系统 / 日志')]
class OperationLogController extends Controller
{
    /**
     * 操作日志
     *
     * @return ApiResponse<Pagination<OperationLog[]>>
     */
    public function index(Request $request): ApiResponse
    {
        $rules = $this->service->activityRules();
        $request->validate($rules);

        return $this->success($this->service->pagination($rules));
    }

    /**
     * 操作日志筛选项
     *
     * @return ApiResponse<array{
     *     events: SelectOption[],
     *     subject_types: SelectOption[]
     * }>
     */
    public function options(): ApiResponse
    {
        $data = $this->service->options(
            ['event', 'subject_type'],
            function (Collection $data) {
                $events = OperationEvent::labels();

                return [
                    'events' => $data->keyBy('event')
                        ->keys()
                        ->sort()
                        ->values()
                        ->map(fn ($item) => [
                            'label' => $events[$item] ?? $item,
                            'value' => $item,
                        ])
                        ->toArray(),
                    'subject_types' => $data->keyBy('subject_type')
                        ->keys()
                        ->sort()
                        ->values()
                        ->map(fn ($item) => ['label' => $item, 'value' => $item])
                        ->toArray(),
                ];
            });

        return $this->success($data);
    }

    #[Override]
    protected function modelClass(): string
    {
        return LogEvent::Operation->config()['model'];
    }
}
