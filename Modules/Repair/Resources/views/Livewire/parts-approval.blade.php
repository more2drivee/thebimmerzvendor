<div>
    <table>
        <thead>
            <tr>
                <th>اسم القطعة</th>
                <th>السعر</th>
                <th>الكمية</th>
                <th>اختيار</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($parts as $part)
                <tr>
                    <td>{{ $part->name }}</td>
                    <td>{{ $part->price }}</td>
                    <td>{{ $part->quantity }}</td>
                    <td>
                        <input type="checkbox" wire:click="toggleApproval({{ $part->id }})"
                            {{ $part->client_approval ? 'checked disabled' : '' }}>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        العدد الكلي: <span>{{ $totalCount }}</span>
        <div>السعر الكلي: <span>{{ $totalPrice }}</span> EGP</div>
    </div>
</div>
