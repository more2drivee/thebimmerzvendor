<div class="btn-group">
    <button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown"
        aria-haspopup="true" aria-expanded="false">
        @lang('messages.action') <span class="caret"></span>
        <span class="sr-only">@lang('messages.action')</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right" role="menu">
        <li>
            <a href="{{ route('sms.messages.show', $row->id) }}">
                <i class="fa fa-eye"></i> @lang('messages.view')
            </a>
        </li>
        <li>
            <a href="{{ route('sms.messages.edit', $row->id) }}">
                <i class="fa fa-pencil"></i> @lang('messages.edit')
            </a>
        </li>
   
        <li class="divider"></li>
        <li>
            <form action="{{ route('sms.messages.destroy', $row->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-link btn-xs text-danger btn-delete">
                    <i class="fa fa-trash"></i> @lang('messages.delete')
                </button>
            </form>
        </li>
    </ul>
</div>
