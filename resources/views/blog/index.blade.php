@extends('layouts.app')
@section('title', __('blog.blogs'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('blog.blogs')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">@lang('blog.manage_your_blogs')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('blog.all_your_blogs')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal pull-right" 
                    data-href="{{action([\App\Http\Controllers\BlogController::class, 'create'])}}" 
                    data-container=".blog_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )
                </button>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="blog_table">
                <thead>
                    <tr>
                        <th>@lang('blog.image')</th>
                        <th>@lang('blog.title')</th>
                        <th>@lang('blog.blog_date')</th>
                        <th>@lang('blog.status')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade blog_modal" tabindex="-1" role="dialog"
    	aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" style="max-width: 600px; margin: auto;"></div>

</section>
<!-- /.content -->

<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script type="text/javascript">
    $(document).ready( function(){
        function initBlogEditor() {
            if ($('textarea#blog_content').length) {
                if (tinymce.get('blog_content')) {
                    tinymce.get('blog_content').remove();
                }
                tinymce.init({
                    selector: 'textarea#blog_content',
                    height: 400,
                    menubar: true,
                    plugins: [
                        'advlist autolink lists link image charmap print preview anchor',
                        'searchreplace visualblocks code fullscreen',
                        'insertdatetime media table paste code help wordcount',
                        'image media'
                    ],
                    toolbar: 'undo redo | formatselect | ' +
                    'bold italic backcolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'removeformat | image media link | code',
                    images_upload_url: '{{ route("blog.upload_image") }}',
                    images_upload_handler: function (blobInfo, success, failure) {
                        var xhr, formData;
                        xhr = new XMLHttpRequest();
                        xhr.withCredentials = false;
                        xhr.open('POST', '{{ route("blog.upload_image") }}');
                        xhr.onload = function() {
                            var json;
                            if (xhr.status != 200) {
                                failure('HTTP Error: ' + xhr.status);
                                return;
                            }
                            json = JSON.parse(xhr.responseText);
                            if (!json || typeof json.location != 'string') {
                                failure('Invalid JSON: ' + xhr.responseText);
                                return;
                            }
                            success(json.location);
                        };
                        formData = new FormData();
                        formData.append('file', blobInfo.blob(), blobInfo.filename());
                        xhr.send(formData);
                    },
                    automatic_uploads: true,
                    file_picker_types: 'image',
                    file_picker_callback: function (callback, value, meta) {
                        var input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/*');

                        input.onchange = function () {
                            var file = this.files[0];
                            var reader = new FileReader();
                            reader.onload = function () {
                                var id = 'blobid' + (new Date()).getTime();
                                var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                                var base64 = reader.result.split(',')[1];
                                var blobInfo = blobCache.create(id, file, base64);
                                blobCache.add(blobInfo);
                                callback(blobInfo.blobUri(), { title: file.name });
                            };
                            reader.readAsDataURL(file);
                        };
                        input.click();
                    },
                    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
                });
            }
        }

        $(document).on('shown.bs.modal', '.blog_modal', function() {
            initBlogEditor();
        });

        $(document).on('hidden.bs.modal', '.blog_modal', function() {
            if (tinymce.get('blog_content')) {
                tinymce.get('blog_content').remove();
            }
        });

        // Debug category modal click
        $(document).on('click', '.btn-modal[data-container=".category_modal"]', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            console.log('[Blog] Category modal button clicked');
            console.log('[Blog] Button data-href:', $(this).data('href'));
            console.log('[Blog] Button data-container:', $(this).data('container'));
            console.log('[Blog] Category modal exists:', $('.category_modal').length);
            console.log('[Blog] Category modal HTML before load:', $('.category_modal').html());

            $.ajax({
                url: $(this).data('href'),
                dataType: 'html',
                success: function(result) {
                    console.log('[Blog] Category modal content loaded, length:', result.length);
                    console.log('[Blog] First 200 chars:', result.substring(0, 200));

                    // Close any open modals first
                    $('.modal').modal('hide');

                    // Remove any existing backdrops
                    $('.modal-backdrop').remove();

                    // Load and show the category modal
                    $('.category_modal').html(result);
                    $('.category_modal').css('z-index', '1055');
                    $('.category_modal').modal('show');

                    console.log('[Blog] Category modal shown');
                    console.log('[Blog] Modal classes:', $('.category_modal').attr('class'));
                    console.log('[Blog] Modal display:', $('.category_modal').css('display'));
                },
                error: function(xhr, status, error) {
                    console.error('[Blog] Failed to load category modal:', error);
                    console.error('[Blog] Response text:', xhr.responseText);
                }
            });
        });

        //Blog table
        var blog_table = $('#blog_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/blogs',
            columnDefs: [ {
                "targets": [0, 4],
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'image', name: 'image' },
                { data: 'title', name: 'title' },
                { data: 'blog_date', name: 'blog_date' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action' },
            ]
        });

        $(document).on('submit', 'form#blog_add_form', function(e) {
            e.preventDefault();
            tinymce.triggerSave();
            $(this)
                .find('button[type="submit"]')
                .attr('disabled', true);
            var data = new FormData(this);

            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                processData: false,
                contentType: false,
                success: function(result) {
                    if (result.success === true) {
                        $('div.blog_modal').modal('hide');
                        toastr.success(result.msg);
                        blog_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', 'button.edit_blog_button', function() {
            $('div.blog_modal').load($(this).data('href'), function() {
                $(this).modal('show');

                $('form#blog_edit_form').submit(function(e) {
                    e.preventDefault();
                    tinymce.triggerSave();
                    $(this)
                        .find('button[type="submit"]')
                        .attr('disabled', true);
                    var data = new FormData(this);

                    $.ajax({
                        method: 'POST',
                        url: $(this).attr('action'),
                        dataType: 'json',
                        data: data,
                        processData: false,
                        contentType: false,
                        success: function(result) {
                            if (result.success === true) {
                                $('div.blog_modal').modal('hide');
                                toastr.success(result.msg);
                                blog_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                });
            });
        });

        $(document).on('click', 'button.delete_blog_button', function() {
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_role,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    var href = $(this).data('href');
                    var data = $(this).serialize();

                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        data: data,
                        success: function(result) {
                            if (result.success === true) {
                                toastr.success(result.msg);
                                blog_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });
    });
</script>
@endsection

