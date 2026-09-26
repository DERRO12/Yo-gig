<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $jobs = Job::with(['client', 'assignedMaid'])->latest()->get();
        $maids = User::where('role', 'maid')->with('maidProfile')->get();

        return view('admin.dashboard', compact('jobs', 'maids'));
    }

    public function matchMaidToJob(Request $request, Job $job)
    {
        $request->validate([
            'maid_id' => 'required|exists:users,id',
        ]);

        $job->update([
            'assigned_maid_id' => $request->maid_id,
            'status'           => 'matched',
        ]);

        return redirect()->back()->with('success', 'Maid assigned to job successfully!');
    }

    public function deleteJob(Job $job)
    {
        $job->delete();

        return redirect()->back()->with('success', 'Job deleted successfully.');
    }
}