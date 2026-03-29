<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{ $product->name }}</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <h4>@lang('lang_v1.models_and_years')</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.car_model')</th>
                                    <th>@lang('lang_v1.brand_category')</th>
                                    <th>@lang('lang_v1.from_year')</th>
                                    <th>@lang('lang_v1.to_year')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($model_years as $model_year)
                                    <tr>
                                        <td>{{ $model_year['model_name'] }}</td>
                                        <td>{{ $model_year['make'] }}</td>
                                        <td>{{ $model_year['from_year'] }}</td>
                                        <td>{{ $model_year['to_year'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">@lang('lang_v1.no_compatibility_data')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
