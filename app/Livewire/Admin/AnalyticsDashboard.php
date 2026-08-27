<?php

namespace App\Livewire\Admin;

use App\Models\RequestLog;
use App\Services\Analytics\AnalyticsDashboardMetrics;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class AnalyticsDashboard extends Component
{
    use WithPagination;

    /**
     * Selected timeframe for analytics: today, 7days, 30days, all, custom.
     */
    public string $timeframe = '7days';

    public bool $isCustomRangeOpen = false;

    public string $customStartDate = '';

    public string $customEndDate = '';

    #[Locked]
    public string $appliedStartDate = '';

    #[Locked]
    public string $appliedEndDate = '';

    /**
     * Filter by route type: all, web, api, admin.
     */
    public string $routeType = 'all';

    /**
     * Search term for IP or Path.
     */
    public string $search = '';

    public function mount(): void
    {
        $this->customStartDate = now()->subDays(6)->toDateString();
        $this->customEndDate = now()->toDateString();
        $this->appliedStartDate = $this->customStartDate;
        $this->appliedEndDate = $this->customEndDate;
    }

    public function openCustomRange(): void
    {
        $this->isCustomRangeOpen = true;
    }

    public function applyCustomRange(): void
    {
        $this->validate([
            'customStartDate' => ['required', 'date_format:Y-m-d', 'before_or_equal:customEndDate', 'before_or_equal:today'],
            'customEndDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:customStartDate', 'before_or_equal:today'],
        ]);

        $this->appliedStartDate = $this->customStartDate;
        $this->appliedEndDate = $this->customEndDate;
        $this->timeframe = 'custom';
        $this->isCustomRangeOpen = false;
        $this->resetPage();
    }

    /**
     * Reset pagination when search is updated.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when route type is updated.
     */
    public function updatingRouteType(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when timeframe is updated.
     */
    public function updatingTimeframe(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component with calculated statistics and logs.
     */
    public function render(AnalyticsDashboardMetrics $metrics): View
    {
        [$startDate, $endDate] = $this->selectedPeriod();
        $snapshot = $metrics->snapshot($this->timeframe, $this->routeType, $startDate, $endDate);

        $recentLogs = $this->forSelectedPeriod(RequestLog::query(), $startDate, $endDate)
            ->when($this->search, function ($query): void {
                $query->where('path', 'like', '%'.$this->search.'%');
            })
            ->when($this->routeType !== 'all', function ($query): void {
                $query->where('route_type', $this->routeType);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.analytics-dashboard', [
            ...$snapshot,
            'recentLogs' => $recentLogs,
        ])->layout('layouts.app');
    }

    /**
     * @return array{0: CarbonInterface|null, 1: CarbonInterface|null}
     */
    private function selectedPeriod(): array
    {
        return match ($this->timeframe) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '30days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'all' => [null, null],
            'custom' => [
                Carbon::createFromFormat('Y-m-d', $this->appliedStartDate)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $this->appliedEndDate)->endOfDay(),
            ],
            default => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
        };
    }

    private function forSelectedPeriod(Builder $query, ?CarbonInterface $startDate, ?CarbonInterface $endDate): Builder
    {
        return $query
            ->when($startDate, fn (Builder $query, CarbonInterface $date): Builder => $query->where('created_at', '>=', $date))
            ->when($endDate, fn (Builder $query, CarbonInterface $date): Builder => $query->where('created_at', '<=', $date));
    }
}
