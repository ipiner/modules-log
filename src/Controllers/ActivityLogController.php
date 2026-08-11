<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Controllers;

use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Override;
use Pin\Http\ApiResponse;
use Pin\Modules\Log\Events\LogEvent;
use Pin\Modules\Log\Models\ActivityLog;
use Pin\Pagination\Pagination;
use Pin\Scramble\SelectOption;

/**
 * 行为日志控制器
 */
#[Group('系统 / 日志')]
class ActivityLogController extends Controller
{
    /**
     * 行为日志
     *
     * @return ApiResponse<Pagination<ActivityLog>>
     */
    public function index(Request $request): ApiResponse
    {
        $rules = $this->service->activityRules();
        $request->validate($rules);

        return $this->success($this->service->pagination($rules));
    }

    /**
     * 行为日志筛选项
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

                return [
                    'events' => $data->keyBy('event')
                        ->keys()
                        ->sort()
                        ->values()
                        ->map(fn ($item) => [
                            'label' => $item,
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
        return LogEvent::Activity->config()['model'];
    }
}
