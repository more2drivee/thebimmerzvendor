<div class="row">
    <div class="col-md-3 text-center">
        <img src="{{ $employee->image_url }}" alt="{{ $employee->full_name }}" class="img-circle" style="width:150px;height:150px;object-fit:cover;">
    </div>
    <div class="col-md-9">
        <table class="table table-bordered">
            <tr>
                <th style="width:30%;">@lang('essentials::lang.employee') @lang('business.name')</th>
                <td>{{ $employee->surname }} {{ $employee->first_name }} {{ $employee->last_name }}</td>
            </tr>
            <tr>
                <th>@lang('lang_v1.email')</th>
                <td>{{ $employee->email }}</td>
            </tr>
            <tr>
                <th>@lang('lang_v1.mobile_number')</th>
                <td>{{ $employee->contact_number }}</td>
            </tr>
            <tr>
                <th>@lang('essentials::lang.department')</th>
                <td>{{ $employee->department_name ?? '-' }}</td>
            </tr>
            <tr>
                <th>@lang('essentials::lang.designation')</th>
                <td>{{ $employee->designation_name ?? '-' }}</td>
            </tr>
            <tr>
                <th>@lang('essentials::lang.location_site')</th>
                <td>{{ $employee->location_name ?? '-' }}</td>
            </tr>
            <tr>
                <th>@lang('essentials::lang.salary')</th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $employee->essentials_salary ?? 0 }}</span></td>
            </tr>
            <tr>
                <th>@lang('essentials::lang.pay_period')</th>
                <td>{{ ucfirst($employee->essentials_pay_period ?? '-') }}</td>
            </tr>
            <tr>
                <th>@lang('business.id_proof_name')</th>
                <td>{{ $employee->id_proof_name ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>
