<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\PaginationHelper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('user')->orderByDesc('created_at');

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('description', 'ilike', $term)
                    ->orWhere('user_name', 'ilike', $term)
                    ->orWhere('auditable_type', 'ilike', $term);
            });
        }

        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $logs = $query->paginate(PaginationHelper::PER_PAGE)->withQueryString();

        $actors = User::query()
            ->whereIn('role', ['super_admin', 'admin', 'staff', 'trainer'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('audit-logs.index', [
            'logs' => $logs,
            'events' => AuditLog::eventOptions(),
            'actors' => $actors,
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('audit-logs.show', compact('auditLog'));
    }
}
