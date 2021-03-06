@extends('layouts.master')
@section('style')
    <link href="{{asset('master/lib/select2/css/select2.min.css')}}" rel="stylesheet">
    <link href="{{asset('master/lib/jquery-ui/jquery-ui.css')}}" rel="stylesheet">
    <link href="{{asset('master/lib/jquery-ui/timepicker/jquery-ui-timepicker-addon.min.css')}}" rel="stylesheet">
    <script src="{{asset('master/lib/vuejs/vue.js')}}"></script>
    <script src="{{asset('master/lib/vuejs/axios.js')}}"></script>
@endsection
@section('content')
    <div class="br-mainpanel" id="app">
        <div class="br-pageheader pd-y-15 pd-l-20">
            <nav class="breadcrumb pd-0 mg-0 tx-12">
                <a class="breadcrumb-item" href="{{route('home')}}">{{__('page.home')}}</a>
                <a class="breadcrumb-item" href="#">{{__('page.purchase_order')}}</a>
                <a class="breadcrumb-item active" href="#">{{__('page.add')}}</a>
            </nav>
        </div>
        <div class="pd-x-20 pd-sm-x-30 pd-t-20 pd-sm-t-30">
            <h4 class="tx-gray-800 mg-b-5"><i class="fa fa-plus-circle"></i> {{__('page.add_purchase_order')}}</h4>
        </div>

        @php
            $role = Auth::user()->role->slug;
        @endphp
        <div class="br-pagebody">
            <div class="br-section-wrapper">
                <form class="form-layout form-layout-1" action="{{route('pre_order.save')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row mg-b-25">
                        <div class="col-md-6">
                            <div class="form-group mg-b-10-force">
                                <label class="form-control-label">{{__('page.date')}}: <span class="tx-danger">*</span></label>
                                <input class="form-control" type="text" name="date" id="pre_order_date" value="{{date('Y-m-d H:i')}}"placeholder="{{__('page.date')}}" autocomplete="off" required>
                                @error('date')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mg-b-10-force">
                                <label class="form-control-label">{{__('page.reference_number')}}:</label>
                                <input class="form-control" type="text" name="reference_number" value="{{ old('reference_number') }}" required placeholder="{{__('page.reference_number')}}">
                                @error('reference_number')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row mg-b-25">
                        <div class="col-md-6">
                            <div class="form-group mg-b-10-force">
                                <label class="form-control-label">{{__('page.supplier')}}:</label>
                                <div class="input-group">                                  
                                    <select class="form-control select2-show-search" name="supplier" id="search_supplier" required data-placeholder="{{__('page.select_supplier')}}">
                                        <option label="{{__('page.select_supplier')}}"></option>
                                        @foreach ($suppliers as $item)
                                            <option value="{{$item->id}}" @if(old('supplier') == $item->id) selected @endif>{{$item->company}}</option>
                                        @endforeach
                                    </select>
                                    <span class="input-group-btn">
                                        <button class="bd bg-primary tx-white ml-1" id="btn-add-supplier" style="border-radius:100px !important;font-size:14px;padding:7px 12px;" type="button"><i class="fa fa-plus"></i></button>
                                    </span>  
                                </div>
                                @error('supplier')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mg-b-10-force">
                                <label class="form-control-label">{{__('page.attachment')}}:</label>
                                <input type="file" name="attachment" id="file2" class="file-input-styled" accept="image/*">
                            </div>
                        </div>
                        {{-- <div class="col-md-6 col-lg-3">
                            <div class="form-group mg-b-10-force">
                                <label class="form-control-label">{{__('page.credit_days')}}:</label>
                                <input type="number" class="form-control" name="credit_days" min=0 value="{{old('credit_days')}}" required placeholder="{{__('page.credit_days')}}" />
                            </div>
                        </div> --}}
                    </div> 
                    <div class="row mg-b-25">
                        <div class="col-md-12">
                            <div>
                                <h5 class="mg-t-10" style="float:left">{{__('page.order_items')}}</h5>
                                <button type="button" class="btn btn-primary btn-icon rounded-circle mg-b-10 add-product" title="{{__('page.right_ctrl_key')}}" style="float:right" @click="add_item()"><div><i class="fa fa-plus"></i></div></button>
                                <button type="button" class="btn btn-sm btn-success mg-b-10 mr-3" id="btn_create_product" title="Shift Key" style="float:right"><div><i class="fa fa-plus"></i> {{__('page.new_product')}}</div></button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-colored table-success" id="product_table">
                                    <thead>
                                        <tr>
                                            <th>{{__('page.product_name_code')}}</th>
                                            <th>{{__('page.product_cost')}}</th>
                                            <th>{{__('page.discount')}}</th>
                                            <th>{{__('page.quantity')}}</th>
                                            <th>{{__('page.subtotal')}}</th>
                                            <th style="width:30px"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="top-search-form">
                                        <tr v-for="(item,i) in order_items" :key="i">
                                            <td>
                                                <input type="hidden" name="product_id[]" class="product_id" :value="item.product_id" />
                                                <input type="text" name="product_name[]" ref="product" class="form-control form-control-sm product" v-model="item.product_name_code" required />
                                            </td>
                                            {{-- <td><input type="date" class="form-control form-control-sm expiry_date" name="expiry_date[]" autocomplete="off" v-model="item.expiry_date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder="{{__('page.expiry_date')}}" /></td> --}}
                                            <td><input type="number" class="form-control form-control-sm cost" name="cost[]" v-model="item.cost" required placeholder="{{__('page.product_cost')}}" /></td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm discount_string" name="discount_string[]" v-model="item.discount_string" required placeholder="{{__('page.discount')}}" />
                                                <input type="hidden" class="discount" name="discount[]" v-model="item.discount" />
                                            </td>
                                            <td><input type="number" class="form-control form-control-sm quantity" name="quantity[]" v-model="item.quantity" required placeholder="{{__('page.quantity')}}" /></td>
                                            {{-- <td class="tax">@{{item.tax_name}}</td> --}}
                                            <td class="subtotal">
                                                @{{formatPrice(item.sub_total)}}
                                                <input type="hidden" name="subtotal[]" :value="item.sub_total" />
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-icon rounded-circle mg-t-3 remove-product" @click="remove(i)"><div style="width:25px;height:25px;"><i class="fa fa-times"></i></div></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2">{{__('page.total')}}</td>
                                            <td class="total_discount">@{{formatPrice(total.discount)}}</td>
                                            <td></td>
                                            <td colspan="2" class="total">
                                                @{{formatPrice(total.cost)}}
                                                <input type="hidden" name="grand_total" :value="grand_total">
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mg-b-10-force">
                                <label class="form-control-label">{{__('page.note')}}:</label>
                                <textarea class="form-control" name="note" rows="5" placeholder="{{__('page.note')}}"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-layout-footer text-right">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-check mg-r-2"></i>{{__('page.save')}}</button>
                        <a href="{{route('pre_order.index')}}" class="btn btn-warning"><i class="fa fa-times mg-r-2"></i>{{__('page.cancel')}}</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="addProductModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{__('page.new_product')}}</h4>
                        <button type="button" class="close" data-dismiss="modal">×</button>
                    </div>
                    <form action="" id="create_product_form" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="form-control-label">{{__('page.product_name')}}: <span class="tx-danger">*</span></label>
                                <input class="form-control" type="text" name="name" placeholder="{{__('page.product_name')}}" required>
                                <span id="product_name_error" class="invalid-feedback">
                                    <strong></strong>
                                </span>                            
                            </div>
                            <div class="form-group">
                                <label class="form-control-label">{{__('page.product_code')}}: <span class="tx-danger">*</span></label>
                                <input class="form-control" type="text" name="code" placeholder="{{__('page.product_code')}}" required>
                                <span id="product_code_error" class="invalid-feedback">
                                    <strong></strong>
                                </span>
                            </div>
                            <div class="form-group">
                                <label class="form-control-label">{{__('page.product_unit')}}: <span class="tx-danger">*</span></label>
                                <input class="form-control" type="text" name="unit" placeholder="{{__('page.product_unit')}}" required>
                                <span id="product_unit_error" class="invalid-feedback">
                                    <strong></strong>
                                </span>
                            </div>
                            <div class="form-group">
                                <label class="form-control-label">{{__('page.product_cost')}}: <span class="tx-danger">*</span></label>
                                <input class="form-control" type="text" name="cost" placeholder="{{__('page.product_cost')}}" required>
                                <span id="product_cost_error" class="invalid-feedback">
                                    <strong></strong>
                                </span>
                            </div>
                            <div class="form-group">
                                <label class="form-control-label">{{__('page.product_image')}}:</label>                                
                                <label class="custom-file wd-100p">
                                    <input type="file" name="image" id="product_image" class="file-input-styled" accept="image/*">
                                </label>
                            </div>
                        </div>    
                        <div class="modal-footer">
                            <button type="button" id="btn_product_create" class="btn btn-primary btn-submit" @click="new_product()"><i class="fa fa-check mg-r-10"></i>&nbsp;{{__('page.save')}}</button>
                            <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times mg-r-10"></i>&nbsp;{{__('page.close')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>        
    </div>

    <div class="modal fade" id="addSupplierModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{__('page.add_supplier')}}</h4>
                    <button type="button" class="close" data-dismiss="modal">×</button>
                </div>
                <form action="" id="create_form" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="control-label">{{__('page.name')}}</label>
                            <input class="form-control name" type="text" name="name" placeholder="{{__('page.name')}}">
                            <span id="name_error" class="invalid-feedback">
                                <strong></strong>
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="control-label">{{__('page.company')}}</label>
                            <input class="form-control company" type="text" name="company" placeholder="{{__('page.company_name')}}">
                            <span id="company_error" class="invalid-feedback">
                                <strong></strong>
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="control-label">{{__('page.email')}}</label>
                            <input class="form-control email" type="email" name="email" placeholder="{{__('page.email_address')}}">
                            <span id="email_error" class="invalid-feedback">
                                <strong></strong>
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="control-label">{{__('page.phone_number')}}</label>
                            <input class="form-control phone_number" type="text" name="phone_number" placeholder="{{__('page.phone_number')}}">
                            <span id="phone_number_error" class="invalid-feedback">
                                <strong></strong>
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="control-label">{{__('page.address')}}</label>
                            <input class="form-control address" type="text" name="address" placeholder="{{__('page.address')}}">
                            <span id="address_error" class="invalid-feedback">
                                <strong></strong>
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="control-label">{{__('page.city')}}</label>
                            <input class="form-control city" type="text" name="city" placeholder="{{__('page.city')}}">
                            <span id="city_error" class="invalid-feedback">
                                <strong></strong>
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="control-label">{{__('page.note')}}</label>
                            <textarea class="form-control note" name="note" rows="3" placeholder="{{__('page.note')}}"></textarea>
                            <span id="note_error" class="invalid-feedback">
                                <strong></strong>
                            </span>
                        </div>
                    </div>    
                    <div class="modal-footer">
                        <button type="button" id="btn_create" class="btn btn-primary btn-submit"><i class="fa fa-check mg-r-10"></i>&nbsp;{{__('page.save')}}</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times mg-r-10"></i>&nbsp;{{__('page.close')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



@endsection
@section('script')
<script src="{{asset('master/lib/select2/js/select2.min.js')}}"></script>
<script src="{{asset('master/lib/jquery-ui/jquery-ui.js')}}"></script>
<script src="{{asset('master/lib/jquery-ui/timepicker/jquery-ui-timepicker-addon.min.js')}}"></script>
<script src="{{asset('master/lib/styling/uniform.min.js')}}"></script>
<script>
    $(document).ready(function () {

        $("#purchase_date").datetimepicker({
            dateFormat: 'yy-mm-dd',
        });
        $(".expiry_date").datepicker({
            dateFormat: 'yy-mm-dd',
        });

        $('.file-input-styled').uniform({
            fileButtonClass: 'action btn bg-primary tx-white'
        });

        $("#btn-add-supplier").click(function(){
            $("#create_form input.form-control").val('');
            $("#create_form .invalid-feedback strong").text('');
            $("#addSupplierModal").modal();
        });

        $("#btn_create").click(function(){
            $("#ajax-loading").show();
            $.ajax({
                url: "{{route('supplier.purchase_create')}}",
                type: 'post',
                dataType: 'json',
                data: $('#create_form').serialize(),
                success : function(data) {
                    $("#ajax-loading").hide();
                    console.log(data);
                    if(data.id != null) {
                        $("#addSupplierModal").modal('hide');
                        $("#search_supplier").append(`
                            <option value="${data.id}">${data.company}</option>
                        `).val(data.id);
                    }
                    else if(data.message == 'The given data was invalid.') {
                        alert(data.message);
                    }
                },
                error: function(data) {
                    $("#ajax-loading").hide();
                    console.log(data.responseJSON);
                    if(data.responseJSON.message == 'The given data was invalid.') {
                        let messages = data.responseJSON.errors;
                        if(messages.name) {
                            $('#name_error strong').text(data.responseJSON.errors.name[0]);
                            $('#name_error').show();
                            $('#create_form .name').focus();
                        }
                        
                        if(messages.company) {
                            $('#company_error strong').text(data.responseJSON.errors.company[0]);
                            $('#company_error').show();
                            $('#create_form .company').focus();
                        }

                        if(messages.email) {
                            $('#email_error strong').text(data.responseJSON.errors.email[0]);
                            $('#email_error').show();
                            $('#create_form .email').focus();
                        }

                        if(messages.phone_number) {
                            $('#phone_number_error strong').text(data.responseJSON.errors.phone_number[0]);
                            $('#phone_number_error').show();
                            $('#create_form .phone_number').focus();
                        }

                        if(messages.address) {
                            $('#address_error strong').text(data.responseJSON.errors.address[0]);
                            $('#address_error').show();
                            $('#create_form .address').focus();
                        }

                        if(messages.city) {
                            $('#city_error strong').text(data.responseJSON.errors.city[0]);
                            $('#city_error').show();
                            $('#create_form .city').focus();
                        }
                    }
                }
            });
        });

        $("#btn_create_product").click(function(){
            $("#addProductModal").modal();
        });
    });
</script>
<script src="{{ asset('js/pre_order_items.js') }}"></script>
@endsection
