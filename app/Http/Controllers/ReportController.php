<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialYear;
use App\Models\Logs;
use App\Models\Employee;
use App\Models\Comboids\Comboids;
use App\Models\SaleBill;
use App\Models\SaleBillTransport;
use Illuminate\Support\Facades\Session;
use DB;

class ReportController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $page_title = 'All Reports';
        $user = Session::get('user');
        $employees = Employee::join('users', 'employees.id', '=', 'users.employee_id')
            ->join('user_groups', 'employees.user_group', '=', 'user_groups.id')
            ->where('employees.id', $user->employee_id)
            ->first();

        return view('reports.index', compact('page_title', 'employees'));
    }

    public function salesRegister(Request $request)
    {
        $page_title = 'Sale Register Report';
        $user = Session::get('user');
        $employees = Employee::join('users', 'employees.id', '=', 'users.employee_id')
            ->join('user_groups', 'employees.user_group', '=', 'user_groups.id')
            ->where('employees.id', $user->employee_id)
            ->first();

        $employees['excelAccess'] = $user->excel_access;

        $logsLastId = Logs::orderBy('id', 'DESC')->first('id');
        $logsId = !empty($logsLastId) ? $logsLastId->id + 1 : 1;

        $logs = new Logs;
        $logs->id = $logsId;
        $logs->employee_id = Session::get('user')->employee_id;
        $logs->log_path = 'Sales Register Report / View';
        $logs->log_subject = 'Sales Register Report view page visited.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();

        return view('reports.sales_register_report', compact('page_title', 'employees'));
    }

    public function listSalesRegisterData(Request $request)
    {
        $data = DB::table('sale_bills as s');
        if ($request->show_detail == 1) {
            $data = $data->join('companies as c', 's.company_id', '=', 'c.id')
                ->selectRaw('SUM(s.total) as total, SUM(s.received_payment) as received_payment, c.name, s.company_id');
        } else {
            $data = $data->join('sale_bill_items as sbi', function ($j) {
                    $j->on('s.sale_bill_id', '=', 'sbi.sale_bill_id')
                    ->on('s.financial_year_id', '=', 'sbi.financial_year_id');
                })
                ->leftJoin('sale_bill_transports as sbt', function ($j) {
                    $j->on('s.sale_bill_id', '=', 'sbt.sale_bill_id')
                    ->on('s.financial_year_id', '=', 'sbt.financial_year_id')
                    ->where('sbt.is_deleted', 0);
                })
                ->leftJoin(DB::raw('(SELECT "company_name", "id" FROM companies group by "company_name", "id") as "cc"'), 's.company_id', '=', 'cc.id')
                ->leftJoin(DB::raw('(SELECT "company_name", "id" FROM companies group by "company_name", "id") as "cs"'), 's.supplier_id', '=', 'cs.id')
                ->selectRaw('cc.company_name as customer_name, cs.company_name as supplier_name, s.select_date, s.sale_bill_id, s.financial_year_id, s.total, s.received_payment, SUM(sbi.pieces) AS tot_pieces, SUM(sbi.meters) AS tot_meters, SUM(sbi.cgst_amount + sbi.sgst_amount + sbi.igst_amount) AS total_gst, sbt.transport_id, sbt.station, sbt.lr_mr_no');
        }
        if ($request->customer && $request->customer->id) {
            $data = $data->where('s.company_id', $request->customer->id);
        }
        if ($request->supplier && $request->supplier->id) {
            $data = $data->where('s.supplier_id', $request->supplier->id);
        }
        if ($request->payment_status && $request->payment_status->id == 1) {
            $data = $data->where('s.payment_status', 0);
        } else if ($request->payment_status && $request->payment_status->id == 2) {
            $data = $data->where('s.payment_status', 1);
        }
        if ($request->start_date && $request->end_date) {
            $data = $data->whereBetween('s.select_date', [$request->start_date, $request->end_date]);
        }
        if ($request->show_detail == 1) {
            $data = $data->whereRaw('s.is_deleted = 0 AND s.sale_bill_flag = 0')
                ->groupBy('c.id')
                ->orderByRaw('c.company_name, s.select_date asc')
                ->get();
        } else {
            $data = $data->whereRaw('s.is_deleted = 0 AND s.sale_bill_flag = 0 AND sbi.is_deleted = 0')
                // ->groupByRaw('s.sale_bill_id, s.financial_year_id')
                ->orderBy('s.sale_bill_id', 'asc')
                ->get();
        }

        return response()->json($data);
    }

}
