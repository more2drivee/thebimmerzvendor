@extends('layouts.app')
@section('title', __('repair::lang.upload_job_sheet_docs'))

@section('content')
@include('repair::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('repair::lang.upload_job_sheet_docs')</h1>
</section>
<!-- Main content -->
<section class="content">
	@component('components.widget', ['class' => 'box-solid'])
		<div class="row">
			<div class="@if($job_sheet->media->count() > 0) col-md-6 @else col-md-12 @endif">
				<table class="table">
					<tr>
						<th>@lang('repair::lang.job_sheet_no'):</th>
						<td>{{$job_sheet->job_sheet_no}}</td>
					</tr>
					<tr>
						<th>@lang('receipt.date'):</th>
						<td>{{@format_datetime($job_sheet->created_at)}}</td>
					</tr>
					<tr>
						<th>
							@lang('role.customer'):
						</th>
						<td>{{$job_sheet->customer->name}}</td>
					</tr>
					<tr>
						<th>@lang('business.location'):</th>
						<td>
							{{$job_sheet->businessLocation?->name}}
						</td>
					</tr>
				</table>
			</div>
			@if($job_sheet->media->count() > 0)
				<div class="col-md-6">
					<div class="row ">
						<div class="col-md-12">
							<h4>
							@lang('repair::lang.uploaded_image_for', ['job_sheet_no' => $job_sheet->job_sheet_no])
							</h4>
						</div>
						<div class="col-md-12">
							@includeIf('repair::job_sheet.partials.document_table_view', ['medias' => $job_sheet->media])
						</div>
					</div>
				</div>
			@endif
		</div>

		@if(isset($booking_media) && $booking_media->count() > 0)
		<div class="row" style="margin-top:15px;">
			<div class="col-md-12">
				<h4>
					@lang('repair::lang.uploaded_image_for', ['job_sheet_no' => $job_sheet->job_sheet_no]) - @lang('restaurant.booking')
				</h4>
			</div>
			<div class="col-md-12">
				@includeIf('repair::job_sheet.partials.document_table_view', ['medias' => $booking_media])
			</div>
		</div>
		@endif

		@if(isset($inspection_documents) && $inspection_documents->isNotEmpty())
		<div class="row" style="margin-top:15px;">
			<div class="col-md-12">
				<h4>
					@lang('checkcar::lang.car_inspections') - @lang('repair::lang.uploaded_image_for', ['job_sheet_no' => $job_sheet->job_sheet_no])
				</h4>
			</div>
			<div class="col-md-12">
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>@lang('repair::lang.type')</th>
							<th>@lang('repair::lang.document')</th>
						</tr>
					</thead>
					<tbody>
						@foreach(['buyer','seller'] as $party)
							@if(isset($inspection_documents[$party]))
								@foreach($inspection_documents[$party] as $doc)
								<tr>
									<td>{{ ucfirst($party) }} - {{ $doc->document_type }}</td>
									<td>
										<a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank">
											{{ basename($doc->file_path) }}
										</a>
									</td>
								</tr>
								@endforeach
							@endif
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
		@endif

	@endcomponent
	@component('components.widget', ['class' => 'box-solid'])
		{!! Form::open(['action' => '\Modules\Repair\Http\Controllers\JobSheetController@postUploadDocs', 'id' => 'job_sheet_doc_upload', 'method' => 'post']) !!}
			<input type="hidden" name="job_sheet_id" value="{{$job_sheet->id}}">
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label for="fileupload">
							@lang('repair::lang.document'):
						</label>
						<div class="dropzone" id="imageUpload"></div>
						<small>
							<p class="help-block">
								@lang('lang_v1.no_file_size_limit')
								@includeIf('components.document_help_text')
							</p>
						</small>
					</div>
					<input type="hidden" id="images" name="images" value="">
				</div>
			</div>
			<button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-w-full pull-right">
				@lang('messages.save')
			</button>
        {!! Form::close() !!}
    @endcomponent
        @php
            $accepted_files = implode(',', array_keys(config('constants.document_upload_mimes_types')));
        @endphp
    </section>
    @stop
    @section('javascript')
<script type="text/javascript">
	Dropzone.autoDiscover = false;
	$(function () {
		var file_names = [];
        // Ensure Dropzone is only initialized once for this element
        var dzElement = document.querySelector('div#imageUpload');
        if (dzElement && !dzElement.dropzone) {
            new Dropzone(dzElement, {
                url: '{{ url("repair/job-sheet-upload-media") }}',
                paramName: 'file',
                uploadMultiple: false,
                autoProcessQueue: true,
                timeout:600000, //10 min
                acceptedFiles: '{{$accepted_files}}',
                maxFiles: 4,
                maxFilesize: null, // No file size limit
                params: {
                    'job_sheet_id': '{{$job_sheet->id}}'
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(file, response) {
                    if (response && response.success) {
                        toastr.success(response.msg);
                        // Push and deduplicate filenames to avoid duplicate records
                        file_names.push(response.file_name);
                        file_names = Array.from(new Set(file_names));
                        $('input#images').val(JSON.stringify(file_names));
                    } else if (response && response.msg) {
                        toastr.error(response.msg);
                    }
                }
            });
        }

        // Prevent accidental double form submission
        $('#job_sheet_doc_upload').on('submit', function(e) {
            var $btn = $(this).find('button[type="submit"]');
            if ($btn.data('submitted')) {
                e.preventDefault();
                return false;
            }
            $btn.data('submitted', true).prop('disabled', true);
        });

	        $(document).on('click', '.delete_media', function (e) {
	            e.preventDefault();
	            var url = $(this).data('href');
	            var this_btn = $(this);
	            swal({
	                title: LANG.sure,
	                icon: "warning",
	                buttons: true,
	                dangerMode: true,
	            }).then((confirmed) => {
	                if (confirmed) {
	                    $.ajax({
	                        method: 'GET',
	                        url: url,
	                        dataType: 'json',
	                        success: function(result) {
	                            if(result.success == true){
			                    this_btn.closest('tr').remove();
			                    toastr.success(result.msg);
			                } else {
			                    toastr.error(result.msg);
			                }
                        }
	                    });
	                }
	            });
	        });
		});
	</script>
@endsection