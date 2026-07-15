<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuickLink;

class QuickLinkController extends Controller
{
    /**
     * Display a listing of the Users-roles.
     */
    public function index()
    {
        // $quickLink = QuickLink::all();
        $quickLink = QuickLink::where('status', '!=', 'deactivate')->get();
        return view('pages.quick-link.index', compact('quickLink'));
    }

    /**
     * Show the form for creating a new User.
     */
    public function create()
    {
        $quickLink = QuickLink::all();
        return view('pages.quick-link.create', compact('quickLink'));
    }

    /**
     * Store a newly created User in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'logo' => 'nullable|string',
            'openurl' => 'nullable|string',
        ]);
        $getList = QuickLink::all();
        $quickLinkStore = new QuickLink();
        $quickLinkStore->srno = $getList->count() + 1;
        $quickLinkStore->name = $request->name;
        $quickLinkStore->url = $request->url;
        $quickLinkStore->logo = $request->logo;
        $quickLinkStore->openurl = $request->openurl;
        $quickLinkStore->status = 'active';
        $quickLinkStore->added_by = auth()->id();
        $quickLinkStore->save();

        return redirect()->route('Quicklink.Index')
            ->with('bg-color', 'success')
            ->with('success', 'QuickLink created successfully.');
    }

    /**
     * Show the form for editing the specified User.
     */
    public function edit($id)
    {
        $quicklink = QuickLink::findOrFail($id);
        return view('pages.quick-link.edit', compact('quicklink'));
    }

    /**
     * Display the specified User.
     */
    public function show($id)
    {
       $quickLink = QuickLink::findOrFail($id);
        return view('pages.quick-link.show', compact('quickLink'));
    }

    /**
     * Update the specified User in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'logo' => 'nullable|string',
            'openurl' => 'nullable|string',
            'status' => 'required|in:active,deactivate,close',
        ]);

        $quickLinkUpdate = QuickLink::findOrFail($id);
        $quickLinkUpdate->name = $request->name;
        $quickLinkUpdate->url = $request->url;
        $quickLinkUpdate->logo = $request->logo;
        $quickLinkUpdate->openurl = $request->openurl;
        $quickLinkUpdate->status = $request->status;
        $quickLinkUpdate->save();

        return redirect()->route('Quicklink.Index')
            ->with('bg-color', 'success')
            ->with('success', 'QuickLink updated successfully.');
    }

    /**
     * Toggle the status of the specified User.
     */
    public function statusUpdate($id)
    {
        $QuickLink = QuickLink::findOrFail($id);
        $QuickLink->status = $QuickLink->status === 'deactivate' ? 'active' : 'deactivate';
        $QuickLink->save();

        return redirect()->route('QuickLink.Index')
            ->with('bg-color', 'success')
            ->with('success', 'QuickLink status updated successfully.');
    }

    /**
     * Remove the specified User from storage.
     */
    public function delete($id)
    {
        $QuickLink = QuickLink::findOrFail($id);
        $QuickLink->status = 'deactivate';
        $QuickLink->save();

        return redirect()->route('Quicklink.Index')
            ->with('bg-color', 'success')
            ->with('success', 'QuickLink deleted successfully.');
    }
}
