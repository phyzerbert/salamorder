<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Company;
use App\Models\Store;
use App\Models\StoreProduct;
use Auth;   

class PurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request) {
        config(['site.page' => 'purchase_list']);
        $user = Auth::user();
        $stores = Store::all();
        $suppliers = Supplier::all();
        $companies = Company::all();

        $mod = new Purchase();
        if($user->role->slug == 'user'){
            $mod = $user->company->purchases();
            $stores = $user->company->stores;
        }
        $company_id = $reference_no = $supplier_id = $store_id = $period = $expiry_period = $keyword = '';
        $sort_by_date = 'desc';
        if ($request->get('company_id') != ""){
            $company_id = $request->get('company_id');
            $mod = $mod->where('company_id', $company_id);
        }
        if ($request->get('reference_no') != ""){
            $reference_no = $request->get('reference_no');
            $mod = $mod->where('reference_no', 'LIKE', "%$reference_no%");
        }
        if ($request->get('supplier_id') != ""){
            $supplier_id = $request->get('supplier_id');
            $mod = $mod->where('supplier_id', $supplier_id);
        }
        if ($request->get('store_id') != ""){
            $store_id = $request->get('store_id');
            $mod = $mod->where('store_id', $store_id);
        }
        if ($request->get('period') != ""){   
            $period = $request->get('period');
            $from = substr($period, 0, 10);
            $to = substr($period, 14, 10);
            $mod = $mod->whereBetween('timestamp', [$from, $to]);
        }
        if ($request->get('expiry_period') != ""){   
            $expiry_period = $request->get('expiry_period');
            $from = substr($expiry_period, 0, 10);
            $to = substr($expiry_period, 14, 10);
            $mod = $mod->whereBetween('expiry_date', [$from, $to]);
        }
        if ($request->get('keyword') != ""){
            $keyword = $request->keyword;
            $company_array = Company::where('name', 'LIKE', "%$keyword%")->pluck('id');
            $supplier_array = Supplier::where('company', 'LIKE', "%$keyword%")->pluck('id');
            $store_array = Store::where('name', 'LIKE', "%$keyword%")->pluck('id');

            $mod = $mod->where(function($query) use($keyword, $company_array, $store_array, $supplier_array){
                return $query->where('reference_no', 'LIKE', "%$keyword%")
                        ->orWhereIn('company_id', $company_array)
                        ->orWhereIn('store_id', $store_array)
                        ->orWhereIn('supplier_id', $supplier_array)
                        ->orWhere('timestamp', 'LIKE', "%$keyword%")
                        ->orWhere('grand_total', 'LIKE', "%$keyword%");
            });
        }
        if($request->sort_by_date != ''){
            $sort_by_date = $request->sort_by_date;
        }
        // dump($sort_by_date);
        $pagesize = session('pagesize');
        $data = $mod->orderBy('timestamp', $sort_by_date)->paginate($pagesize);
        return view('purchase.index', compact('data', 'companies', 'stores', 'suppliers', 'company_id', 'store_id', 'supplier_id', 'reference_no', 'period', 'expiry_period', 'keyword', 'sort_by_date'));
    }

    public function create(Request $request){
        config(['site.page' => 'purchase_create']);
        $user = Auth::user();  
        $suppliers = Supplier::all();
        $products = Product::all();
        $stores = Store::all();
        if($user->hasRole('user')){
            $stores = $user->company->stores;
        }
        return view('purchase.create', compact('suppliers', 'stores', 'products'));
    }

    public function save(Request $request){
        $request->validate([
            'date'=>'required|string',
            'reference_number'=>'required|string',
            'store'=>'required',
            'supplier'=>'required',
            'credit_days' => 'required',
        ]);

        $data = $request->all();
        if(!isset($data['product_id']) ||  count($data['product_id']) == 0 || in_array(null, $data['product_id'])){
            return back()->withErrors(['product' => 'Please select a prouct.']);
        }

        // dd($data);
        $item = new Purchase();
        $item->user_id = Auth::user()->id;  
        $item->timestamp = $data['date'].":00";
        $item->reference_no = $data['reference_number'];
        $item->store_id = $data['store'];
        $store = Store::find($data['store']);
        $item->company_id = $store->company_id;
        $item->supplier_id = $data['supplier'];
        if($data['credit_days'] != ''){
            $item->credit_days = $data['credit_days'];
            $item->expiry_date = date('Y-m-d', strtotime("+".$data['credit_days']."days", strtotime($item->timestamp)));
        }        
        $item->credit_days = $data['credit_days'];
        $item->status = $data['status'];
        $item->note = $data['note'];

        if($request->has("attachment")){
            $picture = request()->file('attachment');
            $imageName = "purchase_".time().'.'.$picture->getClientOriginalExtension();
            $picture->move(public_path('images/uploaded/purchase_images/'), $imageName);
            $item->attachment = 'images/uploaded/purchase_images/'.$imageName;
        }

        $item->discount_string = $data['discount_string'];
        $item->discount = $data['discount'];

        $item->shipping_string = $data['shipping_string'];
        $item->shipping = -1 * $data['shipping'];
        $item->returns = $data['returns'];
        
        $item->grand_total = $data['grand_total'];
        
        $item->save();

        if(isset($data['product_id']) && count($data['product_id']) > 0){

            for ($i=0; $i < count($data['product_id']); $i++) { 
                Order::create([
                    'product_id' => $data['product_id'][$i],
                    'cost' => $data['cost'][$i],
                    'quantity' => $data['quantity'][$i],
                    'expiry_date' => $data['expiry_date'][$i],
                    'subtotal' => $data['subtotal'][$i],
                    'orderable_id' => $item->id,
                    'orderable_type' => Purchase::class,
                ]);
                
                $store_product = StoreProduct::where('store_id', $data['store'])->where('product_id', $data['product_id'][$i])->first();
                if(isset($store_product)){
                    $store_product->increment('quantity', $data['quantity'][$i]);
                }else{
                    $store_product = StoreProduct::create([
                        'store_id' => $data['store'],
                        'product_id' => $data['product_id'][$i],
                        'quantity' => $data['quantity'][$i],
                    ]);
                }
            }
        }
        

        return redirect(route('purchase.index'))->with('success', __('page.created_successfully'));
    }

    public function edit(Request $request, $id){    
        config(['site.page' => 'purchase']); 
        $user = Auth::user();   
        $purchase = Purchase::find($id);        
        $suppliers = Supplier::all();
        $products = Product::all();
        $stores = Store::all();
        if($user->role->slug == 'user'){
            $stores = $user->company->stores;
        }

        return view('purchase.edit', compact('purchase', 'suppliers', 'stores', 'products'));
    }

    public function detail(Request $request, $id){    
        config(['site.page' => 'purchase']);    
        $purchase = Purchase::find($id);

        return view('purchase.detail', compact('purchase'));
    }

    public function update(Request $request){
        $request->validate([
            'date'=>'required|string',
            'reference_number'=>'required|string',
            'store'=>'required',
            'supplier'=>'required',
        ]);
        $data = $request->all();

        if(!isset($data['product_id']) ||  count($data['product_id']) == 0 || in_array(null, $data['product_id'])){
            return back()->withErrors(['product' => 'Please select a prouct.']);
        }
        // dd($data);
        $item = Purchase::find($request->get("id"));
 
        $item->timestamp = $data['date'].":00";
        $item->reference_no = $data['reference_number'];
        $item->store_id = $data['store'];
        $store = Store::find($data['store']);
        $item->company_id = $store->company_id;
        $item->supplier_id = $data['supplier'];
        if($data['credit_days'] != ''){
            $item->credit_days = $data['credit_days'];
            $item->expiry_date = date('Y-m-d', strtotime("+".$data['credit_days']."days", strtotime($item->timestamp)));
        }
        $item->status = $data['status'];
        $item->note = $data['note'];

        if($request->has("attachment")){
            $picture = request()->file('attachment');
            $imageName = "purchase_".time().'.'.$picture->getClientOriginalExtension();
            $picture->move(public_path('images/uploaded/purchase_images/'), $imageName);
            $item->attachment = 'images/uploaded/purchase_images/'.$imageName;
        }

        $item->discount_string = $data['discount_string'];
        $item->discount = $data['discount'];

        $item->shipping_string = $data['shipping_string'];
        $item->shipping = -1 * $data['shipping'];
        $item->returns = $data['returns'];
        
        $item->grand_total = $data['grand_total'];
        
        $item->save();

        if(isset($data['order_id']) && count($data['order_id']) > 0){
            for ($i=0; $i < count($data['order_id']); $i++) { 
                $order = Order::find($data['order_id'][$i]);
                $order_original_quantity = $order->quantity;
                $order->update([
                    'product_id' => $data['product_id'][$i],
                    'cost' => $data['cost'][$i],
                    'quantity' => $data['quantity'][$i],
                    'expiry_date' => $data['expiry_date'][$i],
                    'subtotal' => $data['subtotal'][$i],
                ]);
                if($order_original_quantity != $data['quantity'][$i]){
                    $store_product = StoreProduct::where('store_id', $data['store'])->where('product_id', $data['product_id'][$i])->first();                
                    $store_product->increment('quantity', $data['quantity'][$i]);
                    $store_product->decrement('quantity', $order_original_quantity);
                }
            }
        }
        
        return back()->with('success', __('page.updated_successfully'));
    }

    public function delete($id){
        $item = Purchase::find($id);
        $item->orders()->delete();
        $item->payments()->delete();
        $item->delete();
        return back()->with("success", __('page.deleted_successfully'));
    }
}
