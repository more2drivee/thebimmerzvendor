<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">Follow Up Log for Lead #{{ $lead_id }}</h4>
        </div>
        <div class="modal-body">
            @if($followups->isEmpty())
                <div class="alert alert-info">No follow-ups found for this lead.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Status</th>
                        
                          
                                <th>Assigned To</th>
                          
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($followups as $f)
                                <tr>
                                    <td>{{ $f->title }}</td>
                                    <td>{{ ucfirst($f->schedule_type) }}</td>
                                    <td>{{ ucfirst($f->status) }}</td>
                           
                                    <td>
                                        @foreach($f->users as $user)
                                            <span class="label label-info">{{ $user->first_name }} {{ $user->last_name }}</span><br>
                                        @endforeach
                                    </td>
                                    <td>{!! nl2br(e($f->description)) !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
    </div>
</div> 