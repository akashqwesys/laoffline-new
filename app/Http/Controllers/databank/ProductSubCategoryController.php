<?php

namespace App\Http\Controllers\databank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Employee;
use App\Models\ProductFabricGroup;
use App\Models\ProductCategory;
use App\Models\Logs;
use App\Models\FinancialYear;
use App\Models\Company\Company;
use Illuminate\Support\Facades\Session;
use DB;

class ProductSubCategoryController extends Controller
{
    use HasRoles;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request) {
        $page_title = 'Product Sub Category';
        $financialYear = FinancialYear::get();
        $user = Session::get('user');
        $employees = Employee::join('users', 'employees.id', '=', 'users.employee_id')->
                                join('user_groups', 'employees.user_group', '=', 'user_groups.id')->where('employees.id', $user->employee_id)->first();

        $employees['excelAccess'] = $user->excel_access;

        $logsLastId = Logs::orderBy('id', 'DESC')->first('id');
        $logsId = !empty($logsLastId) ? $logsLastId->id + 1 : 1;

        $logs = new Logs;
        $logs->id = $logsId;
        $logs->employee_id = Session::get('user')->employee_id;
        $logs->log_path = 'ProductSubCategory / View';
        $logs->log_subject = 'Product Sub Category view page visited.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();

        return view('databank.productSubCategories.productSubCategory',compact('financialYear', 'page_title'))->with('employees', $employees);
    }

    public function createProductSubCategory() {
        $page_title = 'Add Product Sub Category';
        $financialYear = FinancialYear::get();
        $user = Session::get('user');
        $employees = Employee::join('users', 'employees.id', '=', 'users.employee_id')->
                                join('user_groups', 'employees.user_group', '=', 'user_groups.id')->where('employees.id', $user->employee_id)->first();

        return view('databank.productSubCategories.createProductSubCategory',compact('financialYear', 'page_title'))->with('employees', $employees);
    }

    public function listData(Request $request) {
        $productSubCategory = ProductCategory::where('main_category_id', '!=', '0')->where('is_delete', '0')->get();

        return $productSubCategory;
    }

    public function listProductSubCategory(Request $request) {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value

        // Total records
        $totalRecords = ProductCategory::select('count(*) as allcount')->count();
        $totalRecordswithFilter = ProductCategory::select('count(product_categories.id) as allcount')
            ->where('product_categories.main_category_id', '<>', '0')
            ->where('product_categories.is_delete', '0');
        if (isset($columnName_arr[2]['search']['value']) && !empty($columnName_arr[2]['search']['value'])) {
            $totalRecordswithFilter = $totalRecordswithFilter->where(function ($q) use ($columnName_arr) {
                $q->orWhere('product_categories.name', 'ILIKE', '%' . $columnName_arr[2]['search']['value'] . '%');
            });
        }
        if (isset($columnName_arr[3]['search']['value']) && !empty($columnName_arr[3]['search']['value'])) {
            $totalRecordswithFilter = $totalRecordswithFilter->where(function ($q) use ($columnName_arr) {
                $q->where('name', 'ILIKE', '%' . $columnName_arr[3]['search']['value'] . '%');
            });
        }
        if (isset($columnName_arr[4]['search']['value']) && !empty($columnName_arr[4]['search']['value'])) {
            $totalRecordswithFilter = $totalRecordswithFilter->join('product_fabric_groups as pfg', 'product_categories.product_fabric_id', '=', 'pfg.id')
            ->where('pfg.name', 'ILIKE', '%' . $columnName_arr[4]['search']['value'] . '%');
        }
        if (isset($columnName_arr[5]['search']['value']) && !empty($columnName_arr[5]['search']['value'])) {
            $cc_id = DB::table('companies')->select('id')->where('company_name', 'ilike', '%' . $columnName_arr[5]['search']['value'] . '%')->first();
            $totalRecordswithFilter = $totalRecordswithFilter->whereRaw("company_id @> '\"" . strval($cc_id->id ?? 0) ."\"' or company_id @> '" . strval($cc_id->id ?? 0) . "'");
        }
        $totalRecordswithFilter = $totalRecordswithFilter->count();

        // Fetch records
        $records = ProductCategory::select('product_categories.*', 'pc1.name as main_category_name')
            ->join('product_categories as pc1', 'product_categories.main_category_id', '=', 'pc1.id')
            // ->where('product_categories.main_category_id', '<>', '0')
            ->where('product_categories.is_delete', '0');
        if (isset($columnName_arr[2]['search']['value']) && !empty($columnName_arr[2]['search']['value'])) {
            $records = $records->where(function ($q) use ($columnName_arr) {
                $q->orWhere('product_categories.name', 'ILIKE', '%' . $columnName_arr[2]['search']['value'] . '%');
            });
        }
        if (isset($columnName_arr[3]['search']['value']) && !empty($columnName_arr[3]['search']['value'])) {
            $records = $records->where(function ($q) use ($columnName_arr) {
                $q->where('pc1.name', 'ILIKE', '%' . $columnName_arr[3]['search']['value'] . '%');
            });
        }
        if (isset($columnName_arr[4]['search']['value']) && !empty($columnName_arr[4]['search']['value'])) {
            $records = $records->join('product_fabric_groups as pfg', 'product_categories.product_fabric_id', '=', 'pfg.id')
                ->where('pfg.name', 'ILIKE', '%' . $columnName_arr[4]['search']['value'] . '%');
        }
        if (isset($columnName_arr[5]['search']['value']) && !empty($columnName_arr[5]['search']['value'])) {
            $records = $records->whereRaw("product_categories.company_id @> '\"" . strval($cc_id->id ?? 0) . "\"' or product_categories.company_id @> '" . strval($cc_id->id ?? 0) . "'");
        }
        if ($columnName == 'main_category') {
            $columnName = 'product_categories.name';
        } else {
            $columnName = 'product_categories.' . $columnName;
        }
        $records = $records->orderBy($columnName,$columnSortOrder)
            ->skip($start)
            ->take($rowperpage == 'all' ? $totalRecords : $rowperpage)
            ->get();
        /* $main_category = collect($records)->where('product_default_category_id', '<>', 0)->pluck('name', 'id');
        $select_ = '<select id="dt_category" class="form-control form-control-sm">';
        foreach ($main_category as $k => $v) {
            $select_ .= '<option value="' . $k . '" > ' . $v . '</option>';
        }
        $select_ .= '</select>'; */
        $data_arr = array();

        foreach($records as $record){
            $id = $record->id;

            $companyId = $record->company_id;
            if (!empty($companyId)) {
                if(is_array(json_decode($companyId))) {
                    $companyName = [];
                    $companyArr = json_decode($companyId);
                    foreach($companyArr as $key => $c) {
                        $company = Company::select('company_name')->where('id', $c)->first('company_name');
                        $companyName[$key] = $company->company_name ?? '';
                    }
                } else {
                    $cId = json_decode($companyId);
                    $company = Company::select('company_name')->where('id', $cId)->first('company_name');
                    $companyName = $company->company_name ?? '';
                }
                $record['companyName'] = is_array($companyName) ? implode(", ", $companyName) : $companyName;
            } else {
                $record['companyName'] = '';
            }

            if ($record->product_fabric_id != 0) {
                $fabricGroup = ProductFabricGroup::select('name')->where('id', $record->product_fabric_id)->first('name');
                $record['fabricGroupName'] = $fabricGroup->name;
            } else {
                $record['fabricGroupName'] = '';
            }

            $name = $record->name;
            $fabric_group = $record['fabricGroupName'];
            $company_name = $record['companyName'];

            /* if ($record->is_active == 1) {
                $active = '<span class="badge badge-dot badge-dot-xs badge-success">Yes</span>';
            } else {
                $active = '<span class="badge badge-dot badge-dot-xs badge-danger">No</span>';
            } */
            $action = '<a href="./productsub-category/edit-productsub-category/'.$id.'" class="btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Update"><em class="icon ni ni-edit-alt"></em></a>
            <a href="./productsub-category/delete/'.$id.'" class="btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Remove"><em class="icon ni ni-trash"></em></a>';

            $data_arr[] = array(
                "id" => $id,
                "name" => $name,
                "main_category" => $record->main_category_name,
                "fabric_group" => $fabric_group,
                "company" => $company_name,
                "action" => $action
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
            // 'main_category' => $select_
        );

        echo json_encode($response);
        exit;
    }

    public function getCompanyName($id) {
        if(is_array(json_decode($id))) {
            $companyName = [];
            $companyArr = json_decode($id);

            foreach($companyArr as $key => $c) {
                $company = Company::where('id', $c)->first('company_name');
                $companyName[$key] = $company->company_name;
            }
        } else {
            $company = Company::where('id', $id)->first('company_name');
            $companyName = $company->company_name;
        }
        $name = is_array($companyName) ? implode(", ", $companyName) : $companyName;

        return $name;
    }

    public function listProductFabricGroup() {
        $productFabricGroup = ProductFabricGroup::all();

        return $productFabricGroup;
    }

    public function listCompanies() {
        $companies = Company::where('is_delete', 0)->get(['company_name', 'id']);

        return $companies;
    }

    public function editProductSubCategory($id) {
        $page_title = 'Edit Product Sub Category';
        $financialYear = FinancialYear::get();
        $user = Session::get('user');
        $employees = Employee::join('users', 'employees.id', '=', 'users.employee_id')->
                                join('user_groups', 'employees.user_group', '=', 'user_groups.id')->where('employees.id', $user->employee_id)->first();

        $employees['scope'] = 'edit';
        $employees['editedId'] = $id;

        return view('databank.productSubCategories.editProductSubCategory',compact('financialYear', 'page_title'))->with('employees', $employees);
    }

    public function fetchProductSubCategory($id) {
        $productSubCategoryData = [];
        $productSubCategory = ProductCategory::where('id', $id)->first();

        $productSubCategoryData['id'] = $productSubCategory->id;
        $productSubCategoryData['multiple_company'] = $productSubCategory->multiple_company;

        if($productSubCategory->multiple_company == 1) {

            $category = ProductCategory::where('id', $productSubCategory->main_category_id)->first(['name as category_name', 'id']);
            $productSubCategoryData['main_category'] = $category;

            if ($productSubCategory->product_fabric_id != 0) {
                $fabricGroup = ProductFabricGroup::where('id', $productSubCategory->product_fabric_id)->first();
                $productSubCategoryData['fabric_group'] = $fabricGroup;
            } else {
                $productSubCategoryData['fabric_group'] = [];
            }

            if(is_array(json_decode($productSubCategory->company_id))) {
                $companyName = [];
                $companyArr = json_decode($productSubCategory->company_id);
                foreach($companyArr as $key => $c) {
                    $company = Company::where('id', $c)->first();
                    $companyName[$key]['company_name'] = $company->company_name;
                    $companyName[$key]['id'] = $company->id;
                }

                $productSubCategoryData['company'] = $companyName;
                $productSubCategoryData['sub_category_name'] = $productSubCategory->name;
                $productSubCategoryData['sort_order'] = $productSubCategory->sort_order;
            }
        } elseif($productSubCategory->multiple_company == 0) {
            $category = ProductCategory::where('id', $productSubCategory->main_category_id)->first(['name as category_name', 'id']);
            $productSubCategoryData['subCategory'][0]['mainCategory'] = $category;

            if ($productSubCategory->product_fabric_id != 0) {
                $fabricGroup = ProductFabricGroup::where('id', $productSubCategory->product_fabric_id)->first();
                $productSubCategoryData['subCategory'][0]['mfabric_group'] = $fabricGroup;
            } else {
                $productSubCategoryData['subCategory'][0]['mfabric_group'] = [];
            }

            $companyName = [];

            $company = Company::where('id', $productSubCategory->company_id)->first();
            $companyName['company_name'] = $company->company_name;
            $companyName['id'] = $company->id;

            $productSubCategoryData['company'] = $companyName;
            $productSubCategoryData['subCategory'][0]['sub_category_name'] = $productSubCategory->name;
            $productSubCategoryData['subCategory'][0]['rate'] = $productSubCategory->rate;
            $productSubCategoryData['subCategory'][0]['sort_order'] = $productSubCategory->sort_order;
        }

        return $productSubCategoryData;
    }

    public function deleteProductSubCategory($id){
        $productCategoryData = ProductCategory::where('id',$id)->first();
        $productCategoryData->is_delete = 1;
        $productCategoryData->save();

        $logsLastId = Logs::orderBy('id', 'DESC')->first('id');
        $logsId = !empty($logsLastId) ? $logsLastId->id + 1 : 1;

        $logs = new Logs;
        $logs->id = $logsId;
        $logs->employee_id = Session::get('user')->employee_id;
        $logs->log_path = 'Product Sub Category / Delete';
        $logs->log_subject = 'Product Sub Category - "'.$productCategoryData->sub_category_name.'" was deleted.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();

        return redirect()->route('productsub-category');
    }

    public function insertProductSubCategoryData(Request $request) {

        if ($request->multiple_company == 1) {
            $this->validate($request, [
                'main_category' => 'required',
                'company' => 'required',
                'sub_category_name' => 'required',
            ]);
        } elseif ($request->multiple_company == 0) {
            $this->validate($request, [
                'singleCompany' => 'required',
                'productSubCategory.*.mainCategory' => 'required',
                'productSubCategory.*.sub_category_name' => 'required',
            ]);
        }

        $company_id = [];

        if($request->multiple_company == 1) {
            foreach($request->company as $key => $com) {
                $company_id[$key] = $com['id'];
            }

            $productCategoryLastId = ProductCategory::orderBy('id', 'DESC')->first('id');
            $productCategoryId = !empty($productCategoryLastId) ? $productCategoryLastId->id + 1 : 1;

            $productCategory = new ProductCategory;
            $productCategory->id = $productCategoryId;
            $productCategory->multiple_company = $request->multiple_company;
            $productCategory->product_default_category_id = 0;
            $productCategory->main_category_id = $request->main_category['id'];
            if ($request->fabric_group != null) {
                $productCategory->product_fabric_id = $request->fabric_group['id'];
            } else {
                $productCategory->product_fabric_id = 0;
            }
            $productCategory->name = $request->sub_category_name;
            $productCategory->company_id = json_encode($company_id);
            $productCategory->rate = 0;
            $productCategory->sort_order = $request->sort_order;
            $productCategory->save();

        } else if($request->multiple_company == 0) {
            foreach($request->productSubCategory as $subCategory) {

                $productCategoryLastId = ProductCategory::orderBy('id', 'DESC')->first('id');
                $productCategoryId = !empty($productCategoryLastId) ? $productCategoryLastId->id + 1 : 1;

                $productCategory = new ProductCategory;
                $productCategory->id = $productCategoryId;
                $productCategory->multiple_company = $request->multiple_company;
                $productCategory->product_default_category_id = 0;
                $productCategory->main_category_id = $subCategory['mainCategory']['id'];
                if ($subCategory['mfabric_group'] != null) {
                    $productCategory->product_fabric_id = $subCategory['mfabric_group']['id'];
                } else {
                    $productCategory->product_fabric_id = 0;
                }
                $productCategory->name = $subCategory['sub_category_name'];
                $productCategory->company_id = $request->singleCompany['id'];
                $productCategory->rate = $subCategory['rate'];
                $productCategory->sort_order = $subCategory['sort_order'];
                $productCategory->save();
            }
        }

        $logsLastId = Logs::orderBy('id', 'DESC')->first('id');
        $logsId = !empty($logsLastId) ? $logsLastId->id + 1 : 1;

        $logs = new Logs;
        $logs->id = $logsId;
        $logs->employee_id = Session::get('user')->employee_id;
        $logs->log_path = 'Product sub category / Add';
        $logs->log_subject = 'Product sub category - "'.$request->sub_category_name.'" was inserted from '.Session::get('user')->username.'.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();
    }

    public function updateProductSubCategoryData(Request $request) {
        if ($request->multiple_company == 1) {
            $this->validate($request, [
                'main_category' => 'required',
                'company' => 'required',
                'sub_category_name' => 'required',
            ]);
        } elseif ($request->multiple_company == 0) {
            $this->validate($request, [
                'singleCompany' => 'required',
                'productSubCategory.*.mainCategory' => 'required',
                'productSubCategory.*.sub_category_name' => 'required',
            ]);
        }

        $id = $request->id;
        $company_id = [];

        if($request->multiple_company == 1) {
            foreach($request->company as $key => $com) {
                $company_id[$key] = $com['id'];
            }
            $productCategory = ProductCategory::where('id', $id)->first();
            $productCategory->multiple_company = $request->multiple_company;
            $productCategory->product_default_category_id = 0;
            $productCategory->main_category_id = $request->main_category['id'];
            if ($request['fabric_group'] != null) {
                $productCategory->product_fabric_id = $request['fabric_group']['id'];
            } else {
                $productCategory->product_fabric_id = 0;
            }
            $productCategory->name = $request->sub_category_name;
            $productCategory->company_id = json_encode($company_id);
            $productCategory->rate = 0;
            $productCategory->sort_order = $request->sort_order;
            $productCategory->save();

        } else if($request->multiple_company == 0) {

            foreach($request->productSubCategory as $key => $subCategory) {
                if ($key == 0) {
                    $productCategory = ProductCategory::where('id', $id)->first();
                } else {
                    $productCategoryLastId = ProductCategory::orderBy('id', 'DESC')->first('id');
                    $productCategoryId = !empty($productCategoryLastId) ? $productCategoryLastId->id + 1 : 1;
                    $productCategory = new ProductCategory;
                    $productCategory->id = $productCategoryId;
                }
                $productCategory->multiple_company = $request->multiple_company;
                $productCategory->product_default_category_id = 0;
                $productCategory->main_category_id = $subCategory['mainCategory']['id'];
                if ($subCategory['mfabric_group'] != null) {
                    $productCategory->product_fabric_id = $subCategory['mfabric_group']['id'];
                } else {
                    $productCategory->product_fabric_id = 0;
                }
                $productCategory->name = $subCategory['sub_category_name'];
                $productCategory->company_id = $request->singleCompany['id'];
                $productCategory->rate = $subCategory['rate'];
                $productCategory->sort_order = $subCategory['sort_order'];
                $productCategory->save();
            }
        }

        $logsLastId = Logs::orderBy('id', 'DESC')->first('id');
        $logsId = !empty($logsLastId) ? $logsLastId->id + 1 : 1;

        $logs = new Logs;
        $logs->id = $logsId;
        $logs->employee_id = Session::get('user')->employee_id;
        $logs->log_path = 'Product sub category / Edit';
        $logs->log_subject = 'Product sub category - "'.$request->sub_category_name.'" was updated from '.Session::get('user')->username.'.';
        $logs->log_url = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $logs->save();
    }
}
