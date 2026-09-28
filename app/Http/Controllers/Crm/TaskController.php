<?php

namespace App\Http\Controllers\Crm;

use App\Enums\ListScope;
use App\Enums\Page;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\TaskRequest;
use App\Models\Task;
use App\Services\Crm\LookupService;
use App\Services\Crm\TaskService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly LookupService $lookups,
    ) {}

    public function allTasks(Request $request): Response
    {
        return $this->render($request, ListScope::All);
    }

    public function myTasks(Request $request): Response
    {
        return $this->render($request, ListScope::Mine, $request->user()->tasks()->getQuery());
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $this->tasks->create($request->validated(), $request->user());

        $this->toast('Task created.');

        return back();
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $this->tasks->update($task, $request->validated());

        $this->toast('Task updated.');

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->tasks->trash($task);

        $this->toast('Task deleted.');

        return back();
    }

    /**
     * @param  Builder<Task>|null  $query
     */
    private function render(Request $request, ListScope $scope, ?Builder $query = null): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
        ]);

        return $this->inertia(Page::TasksIndex, [
            'scope' => $scope,
            'tasks' => $this->tasks->paginate($filters, $query),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            ...$this->lookups->taskForm(),
        ]);
    }
}
