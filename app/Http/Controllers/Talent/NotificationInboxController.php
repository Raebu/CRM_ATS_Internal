<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\{RecruitmentNotification,Tenant};
use Illuminate\Support\Facades\DB;

class NotificationInboxController extends Controller
{
    private function tenant(): Tenant
    {
        return Tenant::where('slug', 'raeburn-group')->firstOrFail();
    }

    public function index()
    {
        return view('talent.notifications', [
            'notifications' => RecruitmentNotification::where('tenant_id', $this->tenant()->id)
                ->latest()->paginate(30),
        ]);
    }

    public function acknowledge(int $id)
    {
        $tenant = $this->tenant();
        DB::transaction(function () use ($tenant, $id) {
            $notification = RecruitmentNotification::where('tenant_id', $tenant->id)
                ->lockForUpdate()->findOrFail($id);
            if ($notification->status === 'pending') {
                $notification->update(['status'=>'acknowledged','acknowledged_at'=>now()]);
            }
        });
        return redirect()->route('admin.talent.notifications.index')
            ->with('success', 'Notification acknowledged.');
    }
}
