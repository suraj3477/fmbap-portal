<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Models\ProgressReport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogController extends Controller
{
    /**
     * Retrieve audit timeline for a specific module record.
     */
    public function index($module, $id)
    {
        $modelClass = match ($module) {
            'fund-release'     => PaymentRequest::class,
            'progress-reports' => ProgressReport::class,
            default            => null,
        };

        if (!$modelClass) {
            return response()->json(['error' => 'Invalid module'], 400);
        }

        $record = $modelClass::findOrFail($id);

        // Security check: if State, ensure they own it
        if (auth()->user()->isState() && $record->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $logs = $record->auditLogs()->with('user:id,name,role')->get()->map(function ($log) {
            return [
                'id'         => $log->id,
                'action'     => $log->action_label,
                'icon'       => $log->action_icon,
                'user'       => $log->user_name ?? 'System',
                'field_name' => $log->field_name ? str_replace('_', ' ', Str::title($log->field_name)) : null,
                'old_value'  => $log->old_value,
                'new_value'  => $log->new_value,
                'remarks'    => $log->remarks,
                'date'       => $log->created_at->format('d M Y, h:i A'),
                'time_ago'   => $log->created_at->diffForHumans(),
            ];
        });

        return response()->json($logs);
    }
}
