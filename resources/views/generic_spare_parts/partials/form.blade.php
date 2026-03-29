<form id="generic_spare_part_form" action="{{ $genericSparePart ? route('generic-spare-parts.update', $genericSparePart->id) : route('generic-spare-parts.store') }}" method="POST">
    @csrf
    @if ($genericSparePart)
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="name">{{ __('generic_spare_parts.name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" value="{{ $genericSparePart->name ?? '' }}" required maxlength="255">
        @error('name')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="description">{{ __('generic_spare_parts.description') }}</label>
        <textarea class="form-control" id="description" name="description" rows="3">{{ $genericSparePart->description ?? '' }}</textarea>
        @error('description')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group text-right">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('generic_spare_parts.cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('generic_spare_parts.save') }}</button>
    </div>
</form>
