@props([
    'region' => '',
    'district' => '',
    'step' => null,
    'regionClass' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]',
    'districtClass' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]',
])

@php
    $locations = \App\Support\TanzaniaLocations::regionsWithDistricts();
    $region = (string) ($region ?? '');
    $district = (string) ($district ?? '');
    $knownDistricts = \App\Support\TanzaniaLocations::districtsFor($region);
    // Keep legacy free-text values visible until the user re-selects.
    if ($district !== '' && $region !== '' && ! in_array($district, $knownDistricts, true)) {
        $locations[$region] = array_values(array_unique(array_merge([$district], $knownDistricts)));
    } elseif ($district !== '' && $region === '') {
        // Unknown region free-text: leave region blank so user must pick a valid region.
    }
@endphp

<div class="mt-4 grid gap-4 sm:grid-cols-2"
     x-data="{
         region: @js($region),
         district: @js($district),
         locations: @js($locations),
         get districts() {
             return this.locations[this.region] || [];
         },
         onRegionChange() {
             if (! this.districts.includes(this.district)) {
                 this.district = '';
             }
         }
     }">
    <div>
        <x-input-label for="region" :value="__('Region')" />
        <select id="region"
                name="region"
                x-model="region"
                @change="onRegionChange()"
                required
                @if($step) data-step="{{ $step }}" @endif
                class="{{ $regionClass }}">
            <option value="">{{ __('Select region') }}</option>
            @foreach(array_keys($locations) as $regionName)
                <option value="{{ $regionName }}">{{ $regionName }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('region')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="district" :value="__('District')" />
        <select id="district"
                name="district"
                x-model="district"
                required
                @if($step) data-step="{{ $step }}" @endif
                class="{{ $districtClass }}">
            <option value="">{{ __('Select district') }}</option>
            <template x-for="name in districts" :key="name">
                <option :value="name" x-text="name"></option>
            </template>
        </select>
        <x-input-error :messages="$errors->get('district')" class="mt-2" />
        @if($district !== '' && $region !== '' && ! in_array($district, $knownDistricts, true))
            <p class="mt-1 text-xs text-amber-700">{{ __('Your saved district is not in the official list. Please select a valid district.') }}</p>
        @endif
    </div>
</div>
