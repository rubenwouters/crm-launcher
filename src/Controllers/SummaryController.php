<?php

namespace Rubenwouters\CrmLauncher\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Rubenwouters\CrmLauncher\Models\Summary;
use Rubenwouters\CrmLauncher\Models\CaseOverview;
use Auth;
use Session;

class SummaryController extends Controller
{
    /**
     * Add summary to case
     * @param Request $request
     * @param integer  $caseId
     * @return view
     */
    public function addSummary(Request $request, $caseId)
    {
        $summary = new Summary();
        $summary->case_id = $caseId;
        $summary->user_id = Auth::user()->id;
        $summary->summary = $request->input('summary');
        $summary->save();

        $this->openCase($caseId);

        Session::flash('flash_success', trans('crm-launcher::success.summary_added'));
        return back();
    }

    /**
     * Delete summary
     * @param  integer $id
     * @param  integer $summaryId
     * @return view
     */
    public function deleteSummary($id, $summaryId)
    {
        $summary = Summary::find($summaryId);

        if ($summary->user_id == Auth::user()->id) {
            $summary->delete();
        }

        Session::flash('flash_success', trans('crm-launcher::success.summary_deleted'));
        return back();
    }

    /**
     * Mark case as "open" (after your first summary is added)
     * @param  integer $caseId
     * @return void
     */
    private function openCase($caseId)
    {
        $case = CaseOverview::find($caseId);
        $case->status = 1;
        $case->save();
        Session::flash('flash_success', trans('crm-launcher::success.case_reopen'));
    }
}
