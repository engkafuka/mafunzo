<p {{ $attributes->merge(['class' => 'mt-1 text-xs text-gray-500']) }}>
    {{ \App\Support\ValidationRules::passwordRequirementsDescription() }}
</p>
