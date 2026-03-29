<a class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm pull-right" 
   data-href="{{action([\App\Http\Controllers\CategoryController::class, 'create'])}}" 
   id="add_category">
    <i class="fa fa-plus"></i>
    @lang('messages.add')
</a>

<br><br>

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="category_table" style="width: 100%">
        <thead>
            <tr>
                <th>@lang('messages.action')</th>
                <th>@lang('messages.category_name')</th>
                <th>@lang('messages.category_type')</th>
                <th>@lang('messages.business')</th>
            </tr>
        </thead>
    </table>
</div>

<div class="modal fade" id="category_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
