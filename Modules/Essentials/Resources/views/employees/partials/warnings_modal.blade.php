@php
    $userId = $id ?? request()->route('id');
@endphp
<div class="row mb-3">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-primary btn-sm add-warning-btn" data-user-id="{{ $userId }}">
            <i class="fas fa-plus"></i> @lang('messages.add')
        </button>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th>@lang('essentials::lang.warning_type')</th>
                <th>@lang('essentials::lang.warning_date')</th>
                <th>@lang('essentials::lang.warning_note')</th>
                <th>@lang('essentials::lang.warning_issued_by')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($warnings as $warning)
            <tr>
                <td>
                    @if($warning->warning_type == 'verbal')
                        <span class="label label-info">@lang('essentials::lang.warning_verbal')</span>
                    @elseif($warning->warning_type == 'written')
                        <span class="label label-warning">@lang('essentials::lang.warning_written')</span>
                    @else
                        <span class="label label-danger">@lang('essentials::lang.warning_final')</span>
                    @endif
                </td>
                <td>{{ @format_date($warning->warning_date) }}</td>
                <td>{{ $warning->warning_note ?? '-' }}</td>
                <td>{{ $warning->issuedBy->full_name ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center text-muted">@lang('lang_v1.no_data')</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
