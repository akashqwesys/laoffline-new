<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Models\FinancialYear;
use App\Models\Logs;
use App\Models\Employee;
use Illuminate\Support\Facades\Session;
use DB;
use PDF;
use Excel;
use App\Exports\SalesRegisterExport;
use App\Exports\ConsolidateMonthlySalesExport;
use App\Exports\CommonExport;

class SalesReportController extends Controller
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
        $page_title = 'Sales Register Report';
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

    // WORKING SIMPLE TABLE
    /* public function listSalesRegisterData(Request $request)
    {
        $data = DB::table('sale_bills as s');
        if ($request->show_detail == 1) {
            $data = $data->join('companies as c', 's.company_id', '=', 'c.id')
                ->selectRaw('SUM(s.total) as total, SUM(s.received_payment) as received_payment, c.company_name, s.company_id');
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
                ->join('cities as c', DB::raw('cast(sbt.station as integer)'), '=', 'c.id')
                ->join('transport_details as td', 'sbt.transport_id', '=', 'td.id')
                ->join('sale_bill_agents as sba', 's.agent_id', '=', 'sba.id')
                ->selectRaw('cc.company_name as customer_name, cs.company_name as supplier_name, s.company_id, s.supplier_id, to_char(s.select_date, \'dd-mm-yyyy\') as select_date, s.sale_bill_id, s.financial_year_id, s.total, s.received_payment, s.change_in_amount, s.sign_change, s.supplier_invoice_no, SUM(sbi.pieces) AS tot_pieces, SUM(sbi.meters) AS tot_meters, SUM(sbi.cgst_amount + sbi.sgst_amount + sbi.igst_amount) AS total_gst, sbt.transport_id, sbt.lr_mr_no, td.name as transport_name, sba.name as agent_name, c.name as city_name');
        }
        if ($request->customer && $request->customer['id']) {
            $data = $data->where('s.company_id', $request->customer['id']);
        }
        if ($request->supplier && $request->supplier['id']) {
            $data = $data->where('s.supplier_id', $request->supplier['id']);
        }
        if ($request->payment_status && $request->payment_status['id'] == 1) {
            $data = $data->where('s.payment_status', 0);
        } else if ($request->payment_status && $request->payment_status['id'] == 2) {
            $data = $data->where('s.payment_status', 1);
        }
        if ($request->start_date && $request->end_date) {
            $data = $data->whereBetween('s.select_date', [$request->start_date, $request->end_date]);
        }
        if ($request->show_detail == 1) {
            $data = $data->whereRaw('s.is_deleted = 0 AND s.sale_bill_flag = 0')
                ->groupByRaw('c.company_name, s.company_id, s.select_date')
                ->orderByRaw('c.company_name, s.select_date asc')
                ->get();
        } else {
            $data = $data->whereRaw('s.is_deleted = 0 AND s.sale_bill_flag = 0 AND sbi.is_deleted = 0')
                ->groupByRaw('s.company_id, s.supplier_id, cc.company_name, cs.company_name, s.select_date, s.sale_bill_id, s.financial_year_id, s.total, s.received_payment, s.change_in_amount, s.sign_change, s.supplier_invoice_no, sbt.transport_id, sbt.lr_mr_no, td.name, sba.name, c.name')
                ->orderBy('s.sale_bill_id', 'asc');
        }
        if ($request->export_pdf == 1) {
            ini_set("memory_limit", -1);
            $data = $data->get();
            $pdf = PDF::loadView('reports.sales_register_export_pdf', compact('data', 'request'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            if ($request->show_detail == 0) {
                $pdf = $pdf->setPaper('a4', 'landscape');
            }
            $path = storage_path('app/public/pdf/sales-register-reports');
            $fileName = 'Sales-Register-Report-' . time() . '.pdf';
            $pdf->save($path . '/' . $fileName);
            return response()->json(['url' => url('/storage/pdf/sales-register-reports/' . $fileName)]);
        } else if ($request->export_sheet == 1) {
            ini_set("memory_limit", -1);
            $data = $data->get();
            $fileName = 'Sales-Register-Report-' . time() . '.xlsx';
            Excel::store(new SalesRegisterExport($data, $request), 'excel-sheets/sales-register-reports/' . $fileName, 'public');
            return response()->json(['url' => url('/storage/excel-sheets/sales-register-reports/' . $fileName)]);
        } else {
            $data = $data
                // ->limit($request->limit ?? 20)
                // ->offset($request->data_offset)
                ->get();
            return response()->json($data);
        }
    } */

    // WORKING DATATABLE TABLE
    public function listSalesRegisterData(Request $request)
    {
        $draw = $request->get('draw');
        if ($draw == 1) {
            return response()->json([
                "draw" => intval($draw),
                "iTotalRecords" => 0,
                "iTotalDisplayRecords" => 0,
                "aaData" => []
            ]);
        }
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        // $columnIndex_arr = $request->get('order');
        // $columnName_arr = $request->get('columns');
        // $order_arr = $request->get('order');
        // $search_arr = $request->get('search');

        // $columnIndex = $columnIndex_arr[0]['column']; // Column index
        // $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        // $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        // $searchValue = $search_arr['value']; // Search value

        $user = Session::get('user');
        $request['customer'] = is_array($request['customer'])
            ? $request['customer']
            : json_decode(json_encode(json_decode($request['customer'])), true);

        $request['supplier'] = is_array($request['supplier'])
            ? $request['supplier']
            : json_decode(json_encode(json_decode($request['supplier'])), true);

        $request['payment_status'] = is_array($request['payment_status'])
            ? $request['payment_status']
            : json_decode(json_encode(json_decode($request['payment_status'])), true);

        $data = DB::table('sale_bills as s');
        if ($request['show_detail'] == 1) {
            $data = $data->join('companies as c', 's.company_id', '=', 'c.id')
                ->selectRaw('SUM(s.total) as total, SUM(s.received_payment) as received_payment, c.company_name, s.company_id');
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
                ->join('cities as c', DB::raw('cast(sbt.station as integer)'), '=', 'c.id')
                ->join('transport_details as td', 'sbt.transport_id', '=', 'td.id')
                ->join('sale_bill_agents as sba', 's.agent_id', '=', 'sba.id')
                ->selectRaw('cc.company_name as customer_name, cs.company_name as supplier_name, s.company_id, s.supplier_id, to_char(s.select_date, \'dd-mm-yyyy\') as select_date, s.sale_bill_id, s.financial_year_id, s.total, s.received_payment, s.change_in_amount, s.sign_change, s.supplier_invoice_no, SUM(sbi.pieces) AS tot_pieces, SUM(sbi.meters) AS tot_meters, SUM(sbi.cgst_amount + sbi.sgst_amount + sbi.igst_amount) AS total_gst, sbt.transport_id, sbt.lr_mr_no, td.name as transport_name, sba.name as agent_name, c.name as city_name');
        }
        if ($request['customer'] && $request['customer']['id']) {
            $data = $data->where('s.company_id', $request['customer']['id']);
        }
        if ($request['supplier'] && $request['supplier']['id']) {
            $data = $data->where('s.supplier_id', $request['supplier']['id']);
        }
        if ($request['payment_status'] && $request['payment_status']['id'] == 1) {
            $data = $data->where('s.payment_status', 0);
        } else if ($request['payment_status'] && $request['payment_status']['id'] == 2) {
            $data = $data->where('s.payment_status', 1);
        }
        if ($request['start_date'] && $request['end_date']) {
            $data = $data->whereBetween('s.select_date', [$request['start_date'], $request['end_date']]);
        }
        if ($request['show_detail'] == 1) {
            $data = $data->whereRaw('s.is_deleted = 0 AND s.sale_bill_flag = 0')
                ->groupByRaw('c.company_name, s.company_id')
                ->orderByRaw('c.company_name asc');
        } else {
            $data = $data->whereRaw('s.is_deleted = 0 AND s.sale_bill_flag = 0 AND sbi.is_deleted = 0')
                ->groupByRaw('s.company_id, s.supplier_id, cc.company_name, cs.company_name, s.select_date, s.sale_bill_id, s.financial_year_id, s.total, s.received_payment, s.change_in_amount, s.sign_change, s.supplier_invoice_no, sbt.transport_id, sbt.lr_mr_no, td.name, sba.name, c.name')
                ->orderBy('s.sale_bill_id', 'asc');
        }
        if ($request['export_pdf'] == 1) {
            ini_set("memory_limit", -1);
            $data = $data->get();
            $pdf = PDF::loadView('reports.sales_register_export_pdf', compact('data', 'request'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            if ($request['show_detail'] == 0) {
                $pdf = $pdf->setPaper('a4', 'landscape');
            }
            $path = storage_path('app/public/pdf/sales-register-reports');
            $fileName = 'Sales-Register-Report-' . time() . '.pdf';
            $pdf->save($path . '/' . $fileName);
            return response()->json(['url' => url('/storage/pdf/sales-register-reports/' . $fileName)]);
        } else if ($request['export_sheet'] == 1) {
            ini_set("memory_limit", -1);
            $data = $data->get();
            $fileName = 'Sales-Register-Report-' . time() . '.xlsx';
            Excel::store(new SalesRegisterExport($data, $request), 'excel-sheets/sales-register-reports/' . $fileName, 'public');
            return response()->json(['url' => url('/storage/excel-sheets/sales-register-reports/' . $fileName)]);
        } else {
            if ($request['show_detail'] == 1) {
                $data_all = $data->get();
                return response()->json($data_all);
            } else {
                $data_all = $data->get();
                $total_pieces = $total_meters = $net_total = $received_total = $gross_total = 0;
                $gross_amount = 0;
                foreach ($data_all as $k => $v) {
                    $total_pieces += floatval($v->tot_pieces);
                    $total_meters += floatval($v->tot_meters);
                    $net_total += floatval($v->total);
                    $received_total += floatval($v->received_payment);
                    if ($v->sign_change == '+') {
                        $gross_amount = (floatval($v->total) - floatval($v->change_in_amount));
                    } else {
                        $gross_amount = (floatval($v->total) + floatval($v->change_in_amount));
                    }
                    $gross_total += $gross_amount;
                }
                $iTotalRecords = count($data_all);
                $iTotalDisplayRecords = count($data_all);

                $data = $data
                    // ->orderBy($columnName, $columnSortOrder)
                    ->skip($start)
                    ->take($rowperpage)
                    ->get();

                $data_arr = [];

                foreach ($data as $k => $v) {
                    if ($v->sign_change == '+') {
                        $gross_amount = (floatval($v->total) - floatval($v->change_in_amount));
                    } else {
                        $gross_amount = (floatval($v->total) + floatval($v->change_in_amount));
                    }
                    $data_arr[] = [
                        'select_date' => $v->select_date,
                        'sale_bill_id' => '<a href="/account/sale-bill/view-sale-bill/' . $v->sale_bill_id . '/' . $v->financial_year_id . '" class="" data-toggle="tooltip" data-placement="top" title="View">' . $v->sale_bill_id . '</a>',
                        'customer_name' => '<a href="#" class="view-details" data-id="' . $v->company_id . '">' . $v->customer_name .'</a>',
                        'tot_pieces' => $v->tot_pieces,
                        'tot_meters' => $v->tot_meters,
                        'total' => $v->total,
                        'received_payment' => $v->received_payment,
                        'total_gst' => $v->total_gst,
                        'agent_name' => $v->agent_name,
                        'supplier_invoice_no' => $v->supplier_invoice_no,
                        'gross_amount' => $gross_amount,
                        'transport_name' => $v->transport_name,
                        'city_name' => $v->city_name,
                        'lr_mr_no' => $v->lr_mr_no,
                        'supplier_name' => '<a href="#" class="view-details" data-id="' . $v->company_id . '">' . $v->supplier_name . '</a>',
                    ];
                }

                $response = array(
                    "draw" => intval($draw),
                    "iTotalRecords" => $iTotalRecords,
                    "iTotalDisplayRecords" => $iTotalDisplayRecords,
                    "aaData" => $data_arr
                );
                return response()->json($response);
            }
        }
    }

    public function consolidateMonthlySales(Request $request)
    {
        $page_title = 'Consolidate Monthly Sales Report';
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
        $logs->log_path = 'Consolidate Monthly Sales Report / View';
        $logs->log_subject = 'Consolidate Monthly Sales Report view page visited.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();

        return view('reports.consolidate_monthly_sales_export', compact('page_title', 'employees'));
    }

    public function listConsolidateMonthlySales(Request $request)
    {
        $data = DB::table('sale_bills as s')
            ->selectRaw("date_trunc('month', s.select_date)::date as month_begin, (date_trunc('month', s.select_date) + interval '1 month -1 day')::date as month_end, SUM(s.total) as total_payment, SUM(CASE WHEN s.pending_payment = 0 and s.payment_status = 0 THEN s.total WHEN s.pending_payment <> 0 and s.payment_status = 0 THEN s.pending_payment ELSE 0 END) AS total_pending, SUM(s.received_payment) as total_received");

        if ($request->city && $request->city['id']) {
            $data = $data->join('sale_bill_transports as sbt', function ($j) {
                $j->on('s.sale_bill_id', '=', 'sbt.sale_bill_id')
                ->on('s.financial_year_id', '=', 'sbt.financial_year_id');
            })
            ->where('sbt.station', $request->city['id']);
        }
        if ($request->customer && $request->customer['id']) {
            $data = $data->where('s.company_id', $request->customer['id']);
        }
        if ($request->supplier && $request->supplier['id']) {
            $data = $data->where('s.supplier_id', $request->supplier['id']);
        }
        if ($request->agent && $request->agent['id'] != 0) {
            $data = $data->where('s.agent_id', $request->agent['id']);
        }
        if ($request->category && $request->category['id']) {
            $data = $data->whereRaw('s.product_category_id @> ' . $request->category['id']);
        }
        if ($request->start_date && $request->end_date) {
            $data = $data->whereBetween('s.select_date', [$request->start_date, $request->end_date]);
        }
        $data = $data->whereRaw('s.is_deleted = 0 AND s.sale_bill_flag = 0')
            ->groupByRaw("date_trunc('month', s.select_date)")
            ->orderByRaw("date_trunc('month', s.select_date) asc")
            ->get();
        foreach ($data as $k => $v) {
            $v->month_year = date('F, Y', strtotime($v->month_begin));
        }

        if ($request->export_pdf == 1) {
            ini_set("memory_limit", -1);
            $pdf = PDF::loadView('reports.consolidate_monthly_sales_export_pdf', compact('data', 'request'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $path = storage_path('app/public/pdf/consolidate-monthly-sales-reports');
            $fileName =  'Consolidate-Monthly-Sales-Report-' . time() . '.pdf';
            $pdf->save($path . '/' . $fileName);
            return response()->json(['url' => url('/storage/pdf/consolidate-monthly-sales-reports/' . $fileName)]);
        } else if ($request->export_sheet == 1) {
            ini_set("memory_limit", -1);
            $fileName =  'Consolidate-Monthly-Sales-Report-' . time() . '.xlsx';
            Excel::store(new ConsolidateMonthlySalesExport($data, $request), 'excel-sheets/consolidate-monthly-sales-reports/' . $fileName, 'public');
            return response()->json(['url' => url('/storage/excel-sheets/consolidate-monthly-sales-reports/' . $fileName)]);
        } else {
            return response()->json($data);
        }
    }

    public function viewMonthlySalesData(Request $request, $start_date, $end_date, $agent, $customer, $supplier)
    {
        $page_title = 'Consolidate Monthly Sales Report';
        $user = Session::get('user');
        $employees = Employee::join('users', 'employees.id', '=', 'users.employee_id')
            ->join('user_groups', 'employees.user_group', '=', 'user_groups.id')
            ->where('employees.id', $user->employee_id)
            ->first();
        return view('reports.consolidate_monthly_sales_company', compact('page_title', 'employees'));
    }

    public function listMonthlySalesData(Request $request)
    {
        $sql = 'is_deleted = 0 and sale_bill_flag = 0';
        $sql_2 = '';
        $sales_total = DB::table('sale_bills')
            ->selectRaw('sum(total) as monthly_total')
            ->where('select_date', '>=', $request->start_date)
            ->where('select_date', '<=', $request->end_date);
        if ($request->agent != 0) {
            $sql .= ' and agent_id = ' . $request->agent;
            $sales_total = $sales_total->where('agent_id', $request->agent);
        }
        if ($request->customer != 0) {
            $sql .= ' and company_id = ' . $request->customer;
            $sql_2 .= ' and company_id = ' . $request->customer;
        }
        if ($request->supplier != 0) {
            $sql .= ' and supplier_id = ' . $request->supplier;
            $sql_2 .= ' and supplier_id = ' . $request->supplier;
        }
        $sales_total = $sales_total->whereRaw('is_deleted = 0 and sale_bill_flag = 0' . $sql_2)
            ->first();

        if ($request->company_type == 2) {
            $which_id = 'company_id';
            $which_id_reverse = 'supplier_id';
        } else {
            $which_id_reverse = 'company_id';
            $which_id = 'supplier_id';
        }

        $sales_report = DB::table('sale_bills')
            ->selectRaw('sum(total) as party_total, ' . $which_id)
            ->where('select_date', '>=', $request->start_date)
            ->where('select_date', '<=', $request->end_date)
            ->whereRaw($sql)
            ->groupBy($which_id)
            ->orderByRaw('sum(total) desc')
            ->get();

        $all_companies = collect($sales_report)
            ->pluck($which_id)
            ->toArray();
        if (!empty($sales_report)) {
            $link_company = $maindata = [];
            foreach ($sales_report as $row) {
                if (!in_array($row->$which_id, $link_company)) {
                    if ($request->company_type == 2) {
                        $cmp_str = $row->company_id;
                        array_push($link_company, $row->company_id);
                        $cust = $this->getCompanyNameFromMultiId($cmp_str);
                        $display_name = "";
                        foreach ($cust as $row_cust) {
                            $display_name .= $row_cust->name . ", ";
                        }
                        $is_display = 1;
                    } else {
                        $is_in_link = $this->get_is_link($row->supplier_id, $all_companies);
                        $is_display = 0;
                        if ($is_in_link == true) {
                            array_push($link_company, $row->supplier_id);
                            $cmp_str = $row->supplier_id;
                            $getlink_company = DB::table('link_companies')
                                ->where('company_id', $cmp_str)
                                ->get();
                            foreach ($getlink_company as $row_link) {
                                if (in_array($row_link->link_companies_id, $all_companies)) {
                                    array_push($link_company, $row_link->link_companies_id);
                                    $cmp_str .= "," . $row_link->link_companies_id;
                                }
                            }
                            $cust = $this->getCompanyNameFromMultiId($cmp_str);
                            $display_name = "";
                            foreach ($cust as $row_cust) {
                                $display_name .= $row_cust->name . ", ";
                            }
                            $is_display = 1;
                        }
                    }
                    if ($is_display == 1) {
                        $sql = "";
                        $grp_sql = "";
                        if ($request->agent != 0) {
                            $grp_sql .= " and s.agent_id = " . $request->agent;
                        }
                        if ($request->company_type == 2) {
                            if ($request->supplier != 0) {
                                $sql .= $cmp_str . '~' . $request->supplier;
                            } else {
                                $sql .= $cmp_str . '~0';
                            }
                            $grp_sql .= " and s.company_id in (" . $cmp_str . ")";
                        } else {
                            if ($request->customer != 0) {
                                $sql .= $request->customer . '~' . $cmp_str;
                            } else {
                                $sql .= '0~' . $cmp_str;
                            }
                            $grp_sql .= " and s.supplier_id in (" . $cmp_str . ")";
                        }
                        $sub_companies = DB::table('sale_bills as s')
                            ->join('companies as c', 's.' . $which_id_reverse, '=', 'c.id')
                            ->selectRaw('sum(s.total) as monthly_total, c.company_name')
                            ->where('select_date', '>=', $request->start_date)
                            ->where('select_date', '<=', $request->end_date)
                            ->whereRaw('s.is_deleted = 0 and s.sale_bill_flag = 0' . $sql_2 . $grp_sql)
                            ->groupByRaw('c.company_name, s.' . $which_id_reverse)
                            ->orderByRaw('sum(s.total) desc')
                            ->get();
                        $maindata[] = [
                            'display_name' => rtrim($display_name, ', '),
                            'party_total' => $row->party_total,
                            'percentage' => round((($row->party_total * 100) / $sales_total->monthly_total), 2) . "%",
                            'sub_companies' => $sub_companies,
                            'sql' => $sql
                            // 'grp_sql' => $grp_sql
                        ];
                    }
                }
            }
            return response()->json([
                'data' => $maindata,
                'total' => ($sales_total ? $sales_total->monthly_total : 0)
            ]);
        }
    }

    public function viewMonthlySalesCompanyData(Request $request, $start_date, $end_date, $agent, $customer, $supplier)
    {
        $page_title = 'Consolidate Monthly Sales Report';
        $user = Session::get('user');
        $employees = Employee::join('users', 'employees.id', '=', 'users.employee_id')
            ->join('user_groups', 'employees.user_group', '=', 'user_groups.id')
            ->where('employees.id', $user->employee_id)
            ->first();

        $sql = 's.is_deleted = 0 and s.sale_bill_flag = 0';
        if ($agent != 0) {
            $sql .= ' and s.agent_id = ' . $agent . " and ";
        }
        if ($customer != 0) {
            $customer = str_replace('-', ',', $customer);
            $sql .= ' and s.company_id in (' . $customer . ") and ";
        }
        if ($supplier != 0) {
            $supplier = str_replace('-', ',', $supplier);
            $sql .= ' and s.supplier_id in (' . $supplier . ") and ";
        }
        $sale_bills = DB::table('sale_bills as s')
            ->leftJoin(DB::raw('(SELECT "company_name", "id" FROM companies group by "company_name", "id") as "cc"'), 's.company_id', '=', 'cc.id')
            ->leftJoin(DB::raw('(SELECT "company_name", "id" FROM companies group by "company_name", "id") as "cs"'), 's.supplier_id', '=', 'cs.id')
            ->select('s.sale_bill_id', 's.financial_year_id', 's.company_id', 's.supplier_id', 's.total', 'cc.company_name as customer_name', 'cs.company_name as supplier_name')
            ->where('s.select_date', '>=', $start_date)
            ->where('s.select_date', '<=', $end_date)
            ->whereRaw(rtrim($sql, 'and '))
            ->orderBy('s.total', 'desc')
            ->get();
        $total = 0;
        foreach ($sale_bills as $v) {
            $total += $v->total;
        }
        $current_month = date('F', strtotime($start_date));
        return view('reports.consolidate_monthly_sales_company_wise', compact('page_title', 'employees', 'sale_bills', 'current_month', 'total'));
    }

    public function listCities()
    {
        $cities = DB::table('cities as c')
            ->join('sale_bill_transports as sbt', 'c.id', '=', DB::raw('cast(station as integer)'))
            ->select('c.id', 'c.name')
            ->groupByRaw('c.id, c.name')
            ->orderBy('c.name', 'asc')
            ->get();
        return response()->json($cities);
    }

    public function getCompanyNameFromMultiId($ids)
    {
        return DB::table('companies')->select('company_name as name')->whereRaw('id in (' . $ids . ')')->get();
    }

    public function get_is_link($id, $all_companies)
    {
        $data = DB::table('link_companies')->select('company_id')->where('link_companies_id', $id)->first();
        if (!empty($data) && in_array($data->company_id, $all_companies)) {
            return false;
        } else {
            return true;
        }
    }

    public function saleBillsDetails(Request $request)
    {
        $page_title = 'Salebill Details Report';
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
        $logs->log_path = 'Salebill Details Report / View';
        $logs->log_subject = 'Salebill Details Report view page visited.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();

        return view('reports.salebill_details_report', compact('page_title', 'employees'));
    }

    public function listSaleBillsDetails(Request $request)
    {
        $user = Session::get('user');

        $data = DB::table('sale_bills as s')
            ->join('sale_bill_items as sbi', function ($j) {
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
            ->join('cities as c', DB::raw('cast(sbt.station as integer)'), '=', 'c.id')
            ->join('transport_details as td', 'sbt.transport_id', '=', 'td.id')
            ->join('sale_bill_agents as sba', 's.agent_id', '=', 'sba.id')
            ->selectRaw('cc.company_name as customer_name, cs.company_name as supplier_name, s.company_id, s.supplier_id, to_char(s.select_date, \'dd-mm-yyyy\') as select_date, s.sale_bill_id, s.financial_year_id, s.total, s.change_in_amount, s.sign_change, s.supplier_invoice_no, sbt.transport_id, sbt.lr_mr_no, td.name as transport_name, sba.name as agent_name, c.name as city_name, s.sale_bill_for, sbi.id, sbi.pieces, sbi.meters, sbi.rate, sbi.amount, (case when s.sale_bill_for = \'1\' then (select product_name from products where category = sbi.product_or_fabric_id limit 1) else (select name from product_categories where id = sbi.product_or_fabric_id limit 1) end) as product_name');

        if ($request->search_type == 'day') {
            $data = $data->where('s.select_date', $request->selected_date);
        } else if ($request->search_type == 'month') {
            $data = $data->whereRaw('to_char(s.select_date, \'mm\') = \'' . $request->selected_month . '\'')
                ->where('s.financial_year_id', $user->financial_year_id);
        } else if ($request->search_type == 'year') {
            $data = $data->where('s.financial_year_id', $user->financial_year_id);
        }

        $data = $data->whereRaw('s.is_deleted = 0 AND s.sale_bill_flag = 0 AND sbi.is_deleted = 0')
            ->groupByRaw('sbi.id, s.company_id, s.supplier_id, s.sale_bill_for, cc.company_name, cs.company_name, s.select_date, s.sale_bill_id, s.financial_year_id, s.total, s.change_in_amount, s.sign_change, s.supplier_invoice_no, sbt.transport_id, sbt.lr_mr_no, td.name, sba.name, c.name, sbi.pieces, sbi.meters, sbi.rate, sbi.amount')
            ->orderByRaw('cc.company_name asc, s.select_date asc');
        $data = $data->get();

        if ($request->export_pdf == 1) {
            ini_set("memory_limit", -1);
            $pdf = PDF::loadView('reports.salebill_details_export_pdf', compact('data', 'request'))
                ->setOptions(['defaultFont' => 'sans-serif'])
                ->setPaper('a4', 'landscape');
            $path = storage_path('app/public/pdf/salebill-details-reports');
            $fileName = 'Salebill-Details-Report-' . time() . '.pdf';
            $pdf->save($path . '/' . $fileName);
            return response()->json(['url' => url('/storage/pdf/salebill-details-reports/' . $fileName)]);
        } else if ($request->export_sheet == 1) {
            ini_set("memory_limit", -1);
            $fileName = 'Salebill-Details-Report-' . time() . '.xlsx';
            Excel::store(new CommonExport($data, $request, 'reports.salebill_details_export_sheet'), 'excel-sheets/salebill-details-reports/' . $fileName, 'public');
            return response()->json(['url' => url('/storage/excel-sheets/salebill-details-reports/' . $fileName)]);
        } else {
            return response()->json($data);
        }
    }

    public function outstandingInvoiceReport(Request $request)
    {
        $page_title = 'Outstanding Invoice Report';
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
        $logs->log_path = 'Outstanding Invoice / View';
        $logs->log_subject = 'Outstanding Invoice view page visited.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();

        return view('reports.outstanding_invoice_report', compact('page_title', 'employees'));
    }

    public function listOutstandingInvoiceReport(Request $request)
    {
        $user = Session::get('user');
        $sql = '';
        if ($request->invoice_no != '') {
            $sql .= " AND ci.bill_no like '%" . $request->invoice_no . "%'";
        }
        if ($request->start_date && $request->end_date) {
            $sql .= " AND ci.bill_date BETWEEN '" . $request->start_date . "' AND '" . $request->end_date . "'";
        }
        if ($request->company && $request->company['id']) {
            if ($request->company['type'] == 2) {
                $sql .= ' and ci.customer_id = ' . $request->company['id'];
            } else {
                $sql .= ' and ci.supplier_id = ' . $request->company['id'];
            }
        }
        if ($request->agent && $request->agent['id'] != 0) {
            $sql .= ' and ci.agent_id = ' . $request->agent['id'];
        }
        if ($request->due_days) {
            $sql .= ' and (DATE_PART(\'day\', now()::timestamp - ci.bill_date::timestamp)) >= ' . $request->due_days;
        }
        if ($request->comm_status == "none") {
            $sql .= " AND ci.commission_status = 0 ";
        } elseif ($request->comm_status == "pending") {
            $sql .= " AND ci.commission_status = 2 ";
        } else {
            $sql .= " AND ci.commission_status != 1 ";
        }
        $sort = '';
        if ($request->sort_by != '') {
            if ($request->sort_by == 1) {
                $sort = 'x.bill_date ASC';
            } elseif ($request->sort_by == 2) {
                $sort = 'x.bill_date DESC';
            } elseif ($request->sort_by == 3) {
                $sort = '(x.final_amount - (case when x.receive_amount IS NULL then 0 else x.receive_amount end)) ASC';
            } else {
                $sort = '(x.final_amount - (case when x.receive_amount IS NULL then 0 else x.receive_amount end)) DESC';
            }
        } else {
            $sort = 'x.id DESC';
        }
        $data = DB::table(DB::raw('(SELECT ci.id, ci.financial_year_id, ci.bill_no, ci.commission_status, to_char(ci.bill_date, \'dd-mm-yyyy\') as bill_date2, ci.customer_id, ci.supplier_id, ci.final_amount, ci.agent_id, CONCAT(e.firstname, \' \', e.lastname) as employee_name, SUM(cd.received_commission_amount) AS receive_amount, (DATE_PART(\'day\', now()::timestamp - ci.bill_date::timestamp)) as due_days, ci.bill_date FROM commission_invoices as ci INNER JOIN employees as e ON ci.generated_by = e.id LEFT JOIN commission_details cd ON cd.commission_invoice_id = ci.id AND cd.is_deleted = 0 WHERE ci.is_deleted = 0 ' . $sql . ' GROUP BY ci.id, e.firstname, e.lastname) as x'))
            ->leftJoin(DB::raw('(SELECT "company_name", "id" FROM companies group by "company_name", "id") as "cc"'), 'x.customer_id', '=', 'cc.id')
            ->leftJoin(DB::raw('(SELECT "company_name", "id" FROM companies group by "company_name", "id") as "cs"'), 'x.supplier_id', '=', 'cs.id')
            ->join('agents as a', 'x.agent_id', '=', 'a.id')
            ->select('x.*', 'a.name as agent_name', 'cc.company_name as customer_name', 'cs.company_name as supplier_name', DB::raw('(x.final_amount - (case when x.receive_amount IS NULL then 0 else x.receive_amount end)) as pending_commission'))
            ->orderByRaw($sort)
            ->get();

        if ($request->export_pdf == 1) {
            ini_set("memory_limit", -1);
            $pdf = PDF::loadView('reports.outstanding_invoice_export_pdf', compact('data', 'request'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $path = storage_path('app/public/pdf/outstanding-invoice-reports');
            $fileName = 'Outstanding-Invoice-Report-' . time() . '.pdf';
            $pdf->save($path . '/' . $fileName);
            return response()->json(['url' => url('/storage/pdf/outstanding-invoice-reports/' . $fileName)]);
        } else if ($request->export_sheet == 1) {
            ini_set("memory_limit", -1);
            $fileName = 'Outstanding-Invoice-Report-' . time() . '.xlsx';
            Excel::store(new CommonExport($data, $request, 'reports.outstanding_invoice_export_sheet'), 'excel-sheets/outstanding-invoice-reports/' . $fileName, 'public');
            return response()->json(['url' => url('/storage/excel-sheets/outstanding-invoice-reports/' . $fileName)]);
        } else {
            return response()->json($data);
        }
    }

    public function commissionInvoiceReport(Request $request)
    {
        $page_title = 'Commission Invoice Report';
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
        $logs->log_path = 'Commission Invoice / View';
        $logs->log_subject = 'Commission Invoice view page visited.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();

        return view('reports.commission_invoice_report', compact('page_title', 'employees'));
    }

    public function listCommissionInvoiceReport(Request $request)
    {
        $user = Session::get('user');
        $sql = '';
        if ($request->invoice_no != '') {
            $sql .= " and ci.bill_no like '%" . $request->invoice_no . "%'";
        }
        if ($request->start_date && $request->end_date) {
            $sql .= " and ci.bill_date between '" . $request->start_date . "' and '" . $request->end_date . "'";
        }
        if ($request->company && $request->company['id']) {
            $sql .= ' and (ci.customer_id = ' . $request->company['id'] . ' or ci.supplier_id = ' . $request->company['id'] . ')';
        }
        if ($request->agent && $request->agent['id'] != 0) {
            $sql .= ' and ci.agent_id = ' . $request->agent['id'];
        }
        if ($request->due_days) {
            $sql .= ' and (DATE_PART(\'day\', now()::timestamp - ci.bill_date::timestamp)) >= ' . $request->due_days;
        }
        $sort = '';
        if ($request->sort_by != '') {
            if ($request->sort_by == 1) {
                $sort = 'ci.bill_date ASC';
            } else {
                $sort = 'ci.bill_date DESC';
            }
        } else {
            $sort = 'ci.id DESC';
        }
        $data = DB::table('commission_invoices as ci')
            ->leftJoin(DB::raw('(SELECT "company_name", "id", "company_state" FROM companies group by "company_name", "id", "company_state") as "cc"'), 'ci.customer_id', '=', 'cc.id')
            ->leftJoin(DB::raw('(SELECT "company_name", "id", "company_state" FROM companies group by "company_name", "id", "company_state") as "cs"'), 'ci.supplier_id', '=', 'cs.id')
            ->join('states as s', DB::raw('(case when "cc"."id" is null then "cs"."company_state" else "cc"."company_state" end)'), '=', 's.id')
            ->join('agents as a', 'ci.agent_id', '=', 'a.id')
            ->select('ci.bill_no', 'ci.id', 'ci.financial_year_id', 'a.name as agent_name', 'cc.company_name as customer_name', 'cs.company_name as supplier_name', 's.name as state_name', DB::raw('to_char(ci.bill_date, \'dd-mm-yyyy\') as bill_date2'), 'ci.commission_amount', 'ci.cgst_amount', 'ci.sgst_amount', 'ci.igst_amount', 'ci.tds_amount', 'ci.final_amount')
            ->whereRaw('ci.is_deleted = 0 ' . $sql)
            ->orderByRaw($sort)
            ->get();

        if ($request->export_pdf == 1) {
            ini_set("memory_limit", -1);
            $pdf = PDF::loadView('reports.commission_invoice_export_pdf', compact('data', 'request'))
                ->setOptions(['defaultFont' => 'sans-serif'])
                ->setPaper('a4', 'landscape');
            $path = storage_path('app/public/pdf/commission-invoice-reports');
            $fileName = 'Commission-Invoice-Report-' . time() . '.pdf';
            $pdf->save($path . '/' . $fileName);
            return response()->json(['url' => url('/storage/pdf/commission-invoice-reports/' . $fileName)]);
        } else if ($request->export_sheet == 1) {
            ini_set("memory_limit", -1);
            $fileName = 'Commission-Invoice-Report-' . time() . '.xlsx';
            Excel::store(new CommonExport($data, $request, 'reports.commission_invoice_export_sheet'), 'excel-sheets/commission-invoice-reports/' . $fileName, 'public');
            return response()->json(['url' => url('/storage/excel-sheets/commission-invoice-reports/' . $fileName)]);
        } else {
            return response()->json($data);
        }
    }

    public function commissionInvoiceRightofReport(Request $request)
    {
        $page_title = 'Commission Invoice Right of Report';
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
        $logs->log_path = 'Commission Invoice Right of / View';
        $logs->log_subject = 'Commission Invoice Right of view page visited.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();

        return view('reports.commission_invoice_right_report', compact('page_title', 'employees'));
    }

    public function listCommissionInvoiceRightofReport(Request $request)
    {
        $user = Session::get('user');
        $sql = '';
        if ($request->start_date && $request->end_date) {
            $sql .= " and p.date between '" . $request->start_date . "' and '" . $request->end_date . "'";
        }
        if ($request->customer && $request->customer['id']) {
            $sql .= ' and p.receipt_from = ' . $request->customer['id'];
        }
        if ($request->supplier && $request->supplier['id']) {
            $sql .= ' and p.supplier_id = ' . $request->supplier['id'];
        }
        $payment_sort = "cs.company_name ASC, p.payment_id ASC";
        if ($request->sort_by == 1) {
            $payment_sort = "cs.company_name ASC, p.payment_id ASC";
        } else if ($request->sort_by == 2) {
            $payment_sort = "cs.company_name DESC, p.payment_id ASC";
        } else if ($request->sort_by == 3) {
            $payment_sort = "cc.company_name ASC, p.payment_id ASC";
        } else if ($request->sort_by == 4) {
            $payment_sort = "cc.company_name DESC, p.payment_id ASC";
        } else if ($request->sort_by == 5) {
            $payment_sort = "p.date ASC, p.payment_id ASC";
        } else if ($request->sort_by == 6) {
            $payment_sort = "p.date DESC, p.payment_id ASC";
        }
        $data = DB::table('payments as p')
            ->join(DB::raw('(SELECT "company_name", "id", "company_state" FROM companies group by "company_name", "id", "company_state") as "cc"'), 'p.receipt_from', '=', 'cc.id')
            ->join(DB::raw('(SELECT "company_name", "id", "company_state" FROM companies group by "company_name", "id", "company_state") as "cs"'), 'p.supplier_id', '=', 'cs.id')
            ->leftJoin('bank_details as bd', 'p.deposite_bank', '=', 'bd.id')
            ->leftJoin('bank_details as bd1', 'p.cheque_dd_bank', '=', 'bd1.id')
            ->select('p.payment_id', 'p.id', 'p.financial_year_id', 'bd.name as bank_name', 'bd1.name as deposit_bank', 'cc.company_name as customer_name', 'cs.company_name as supplier_name', DB::raw('to_char(p.date, \'dd-mm-yyyy\') as date2, to_char(p.cheque_date, \'dd-mm-yyyy\') as cheque_date2'), 'p.reciept_mode', 'p.cheque_dd_no', 'p.receipt_amount', 'p.total_amount', 'p.right_of_amount', 'p.right_of_remark')
            ->whereRaw('p.is_deleted = 0 and p.right_of_amount <> 0 ' . $sql)
            ->orderByRaw($payment_sort)
            ->get();

        if ($request->export_pdf == 1) {
            ini_set("memory_limit", -1);
            $pdf = PDF::loadView('reports.commission_invoice_right_of_export_pdf', compact('data', 'request'))
                ->setOptions(['defaultFont' => 'sans-serif'])
                ->setPaper('a4', 'landscape');
            $path = storage_path('app/public/pdf/commission-invoice-right-of-reports');
            $fileName = 'Commission-Invoice-Right-of-Report-' . time() . '.pdf';
            $pdf->save($path . '/' . $fileName);
            return response()->json(['url' => url('/storage/pdf/commission-invoice-right-of-reports/' . $fileName)]);
        } else if ($request->export_sheet == 1) {
            ini_set("memory_limit", -1);
            $fileName = 'Commission-Invoice-Right-of-Report-' . time() . '.xlsx';
            Excel::store(new CommonExport($data, $request, 'reports.commission_invoice_right_of_export_sheet'), 'excel-sheets/commission-invoice-right-of-reports/' . $fileName, 'public');
            return response()->json(['url' => url('/storage/excel-sheets/commission-invoice-right-of-reports/' . $fileName)]);
        } else {
            return response()->json($data);
        }
    }

    public function percentageEvaluateReport(Request $request)
    {
        $page_title = 'Percentage Evaluate Report';
        $user = Session::get('user');
        $employees = Employee::join('users', 'employees.id', '=', 'users.employee_id')
            ->join('user_groups', 'employees.user_group', '=', 'user_groups.id')
            ->where('employees.id', $user->employee_id)
            ->first();

        $logsLastId = Logs::orderBy('id', 'DESC')->first('id');
        $logsId = !empty($logsLastId) ? $logsLastId->id + 1 : 1;

        $logs = new Logs;
        $logs->id = $logsId;
        $logs->employee_id = Session::get('user')->employee_id;
        $logs->log_path = 'Percentage Evaluate / View';
        $logs->log_subject = 'Percentage Evaluate view page visited.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();

        return view('reports.percentage_evaluate_report', compact('page_title', 'employees'));
    }

    public function listPercentageEvaluateReport(Request $request)
    {
        $user = Session::get('user');
        $sql = '';
        if ($request->start_date && $request->end_date) {
            $sql .= "bill_date between '" . $request->start_date . "' and '" . $request->end_date . "'";
        }
        if ($request->company_type == 2) {
            $sql .= ' and customer_id <> 0';
        } else if ($request->company_type == 3) {
            $sql .= ' and supplier_id <> 0';
        }
        if ($request->sort_by == "1") {    // customer
            $sort = "commission_percent ASC";
        } else if ($request->sort_by == "2") {    // supplier
            $sort = "commission_percent DESC";
        } else if ($request->sort_by == "3") {    // supplier
            $sort = "total_amount ASC";
        } else if ($request->sort_by == "4") {    // supplier
            $sort = "total_amount DESC";
        }

        $total = DB::table('commission_invoices')
            ->selectRaw('sum(commission_amount) as total_commission_amount')
            ->whereRaw($sql)
            ->pluck('total_commission_amount')
            ->first();

        $data = DB::table('commission_invoices')
            ->selectRaw('commission_percent, SUM(commission_amount) as total_amount')
            ->whereRaw($sql)
            ->orderByRaw($sort)
            ->groupBy('commission_percent')
            ->get();

        return response()->json(['data' => $data, 'total' => $total]);
    }

    public function percentageEvaluateTurnoverReport(Request $request)
    {
        $page_title = 'Percentage Evaluate Turnover Report';
        $user = Session::get('user');
        $employees = Employee::join('users', 'employees.id', '=', 'users.employee_id')
            ->join('user_groups', 'employees.user_group', '=', 'user_groups.id')
            ->where('employees.id', $user->employee_id)
            ->first();

        $logsLastId = Logs::orderBy('id', 'DESC')->first('id');
        $logsId = !empty($logsLastId) ? $logsLastId->id + 1 : 1;

        $logs = new Logs;
        $logs->id = $logsId;
        $logs->employee_id = Session::get('user')->employee_id;
        $logs->log_path = 'Percentage Evaluate Turnover / View';
        $logs->log_subject = 'Percentage Evaluate Turnover view page visited.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();

        return view('reports.percentage_evaluate_turnover_report', compact('page_title', 'employees'));
    }

    public function listPercentageEvaluateTurnoverReport(Request $request)
    {
        $user = Session::get('user');
        $sql = 's.is_deleted = 0 AND s.sale_bill_flag = 0 ';
        if ($request->start_date && $request->end_date) {
            $sql .= " and s.select_date between '" . $request->start_date . "' and '" . $request->end_date . "'";
        }
        if ($request->company_type == 2) {
            $sql .= ' and s.customer_id <> 0';
        } else if ($request->company_type == 3) {
            $sql .= ' and s.supplier_id <> 0';
        }
        if ($request->sort_by == "1") {    // customer
            $sort = "commission_percentage ASC";
        } else if ($request->sort_by == "2") {    // supplier
            $sort = "commission_percentage DESC";
        } else if ($request->sort_by == "3") {    // supplier
            $sort = "total_amount ASC";
        } else if ($request->sort_by == "4") {    // supplier
            $sort = "total_amount DESC";
        }

        $total = DB::table('sale_bills as s')
            ->join('company_commissions as cc', function ($j) {
                $j->on('s.supplier_id', '=', 'cc.supplier_id')
                ->on('s.company_id', '=', 'cc.customer_id');
            })
            ->selectRaw('sum(s.total) AS total_turnover_amount')
            ->whereRaw($sql)
            ->pluck('total_turnover_amount')
            ->first();

        $data = DB::table(DB::raw('(SELECT s.total, (case when cc.commission_percentage IS NULL then 2 else cc.commission_percentage end) as commission_percentage FROM "sale_bills" as s
				INNER JOIN company_commissions as cc ON cc.supplier_id = s.supplier_id AND cc.customer_id = s.company_id
				WHERE ' . $sql . ') as x'))
            ->selectRaw('SUM(x.total) as total_amount, x.commission_percentage')
            ->groupBy('commission_percentage')
            ->orderByRaw($sort)
            ->get();

        return response()->json(['data' => $data, 'total' => $total]);
    }
}
