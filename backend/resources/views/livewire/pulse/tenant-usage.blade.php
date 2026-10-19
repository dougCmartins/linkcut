<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header name="Tenants" details="top 10 by requests, past {{ $this->periodForHumans() }}">
        <x-slot:icon>
            <x-pulse::icons.arrow-trending-up />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($tenants->isEmpty())
            <x-pulse::no-results />
        @else
            <x-pulse::table>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Tenant</x-pulse::th>
                        <x-pulse::th class="text-right">Requests</x-pulse::th>
                        <x-pulse::th class="text-right">5xx</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($tenants as $row)
                        <tr wire:key="{{ $row['tenant'] }}">
                            <x-pulse::td>{{ $row['tenant'] }}</x-pulse::td>
                            <x-pulse::td numeric class="text-right">{{ number_format($row['requests']) }}</x-pulse::td>
                            <x-pulse::td numeric class="text-right {{ $row['errors'] ? 'text-red-600' : '' }}">{{ number_format($row['errors']) }}</x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
