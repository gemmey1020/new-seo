<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflow\SeoTask;
use App\Models\Workflow\SeoTaskComment;

class TaskController extends Controller
{
    /**
     * Index.
     */
    public function index(Request $request, $siteId)
    {
        return SeoTask::where('site_id', $siteId)->paginate(50);
    }

    /**
     * Store Task.
     */
    public function store(Request $request, $siteId)
    {
        $data = $request->all();
        $data['site_id'] = $siteId;
        $data['created_by_user_id'] = auth()->id();
        
        return SeoTask::create($data);
    }

    /**
     * Update Task.
     */
    public function update(Request $request, $siteId, $taskId)
    {
        $task = SeoTask::where('site_id', $siteId)->findOrFail($taskId);
        $task->update($request->all());
        return $task;
    }

    /**
     * Store Comment.
     */
    public function storeComment(Request $request, $siteId, $taskId)
    {
        $task = SeoTask::where('site_id', $siteId)->findOrFail($taskId);
        
        return SeoTaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'body' => $request->input('body')
        ]);
    }
}
