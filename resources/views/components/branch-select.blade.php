@if(isset($branches) && $branches->count() > 1)
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
        <select name="lab_branch_id" class="w-full border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500">
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}"
                    {{ old('lab_branch_id', $selectedBranchId ?? ($branch->is_central ? $branch->id : '')) == $branch->id ? 'selected' : '' }}>
                    {{ $branch->name }}{{ $branch->city ? ' — ' . $branch->city : '' }}
                </option>
            @endforeach
        </select>
    </div>
@elseif(isset($branches) && $branches->count() == 1)
    <input type="hidden" name="lab_branch_id" value="{{ $branches->first()->id }}">
@endif
