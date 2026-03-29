@extends('layouts.app')

@section('content')
    <section class="content-header no-print">
        <div class="max-w-xl mx-auto bg-white p-4 rounded-lg shadow-md">
            <div class="flex items-center space-x-2">
                <h2 class="text-lg font-bold">{{ $usercreatename }}</h2>
            </div>
            <p class="mt-2 text-gray-700" style="color:black;">{{ $message->message }}</p>

            <div class="mt-3 flex items-center space-x-2">
                <span>{{ count($commentes) }} {{ Str::plural('Comment', count($commentes)) }}</span>
            </div>

            <hr class="my-3">

            <h3 class="text-md font-semibold">Comments</h3>

            @if (empty($commentes))
                <p class="text-gray-500">No Comments Yet.</p>
            @else
                @foreach ($commentes as $comment)
                    <div class="bg-gray-50 p-3 rounded-lg mt-2">
                        <h4 class="font-bold">{{ $comment['userName'] }}</h4>
                        <p class="text-gray-600" style="color:black;">{{ $comment['comment'] }}</p>
                    </div>
                @endforeach
            @endif
        </div>
        <form action="{{ route('chage.status', ['id' => $message->id]) }}" method="GET">
            <div class="row" style="margin-top: 7px;">
                <div class="col-sm-12">
                    <div class="text-center">
                        <div class="btn-group">
                            <button type="submit" value="submit"
                                class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">
                                <?php echo $message->status == 0 ? 'Deactivate' : 'Active'; ?>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection
